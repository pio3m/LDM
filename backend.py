import os
import json
import datetime
import requests
import time
from dotenv import load_dotenv
from flask import Flask, request, jsonify, render_template
from langchain.chat_models import ChatOpenAI
from langchain.agents import AgentType, initialize_agent, Tool
from rich.console import Console
from rich.progress import Progress, SpinnerColumn, TextColumn

load_dotenv()

console = Console()
app = Flask(__name__, template_folder="templates")

llm = ChatOpenAI(model_name="gpt-4", openai_api_key=os.getenv("OPENAI_API_KEY"))

VEHICLES = {
    "bus": {"width": 240, "height": 260, "length": 450},
    "solowka": {"width": 240, "height": 260, "length": 730},
    "naczepa": {"width": 240, "height": 260, "length": 1360},
    "ponadgabaryt": {"width": float('inf'), "height": float('inf'), "length": float('inf')}
}

def get_current_datetime():
    return datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')

def geocode_address(address: str):
    url = "https://nominatim.openstreetmap.org/search"
    params = {"q": address, "format": "json", "limit": 1}
    headers = {"User-Agent": "TransportApp/1.0"}
    response = requests.get(url, params=params, headers=headers)
    data = response.json()
    return (None, None) if not data else (float(data[0]["lat"]), float(data[0]["lon"]))

def get_osrm_distance(lat1, lon1, lat2, lon2):
    url = f"https://router.project-osrm.org/route/v1/driving/{lon1},{lat1};{lon2},{lat2}"
    headers = {"User-Agent": "TransportApp/1.0"}
    response = requests.get(url, headers=headers)
    data = response.json()
    return data["routes"][0]["distance"] / 1000.0 if "routes" in data and data["routes"] else -1.0

def estimate_distance(postal_code_from, postal_code_to):
    lat1, lon1 = geocode_address(postal_code_from)
    lat2, lon2 = geocode_address(postal_code_to)
    return None if None in (lat1, lon1, lat2, lon2) else get_osrm_distance(lat1, lon1, lat2, lon2)

def validate_cargo_dimensions(loads):
    for load in loads:
        if load["height"] > VEHICLES["naczepa"]["height"]:
            return {"error": "Ładunek jest za wysoki dla dostępnych pojazdów."}
        if load["width"] > VEHICLES["naczepa"]["width"] and load["length"] > VEHICLES["naczepa"]["width"]:
            return {"error": "Ładunek wymaga transportu ponadgabarytowego."}
    return None

def calculate_ldm(loads):
    remaining_width = VEHICLES["naczepa"]["width"]
    total_ldm = 0.0

    sorted_loads = sorted(loads, key=lambda x: max(x["width"], x["length"]), reverse=True)

    for load in sorted_loads:
        width, length = load["width"], load["length"]

        if width > VEHICLES["naczepa"]["width"] or length > VEHICLES["naczepa"]["width"]:
            total_ldm += length / 100.0
            remaining_width -= width
            continue

        if width > length:
            width, length = length, width

        num_fit_by_width = VEHICLES["naczepa"]["width"] // width
        num_fit_by_length = VEHICLES["naczepa"]["width"] // length

        if num_fit_by_width > num_fit_by_length:
            ldm_per_row = length / 100.0
            max_in_row = num_fit_by_width
        else:
            ldm_per_row = width / 100.0
            max_in_row = num_fit_by_length

        num_rows = (load["quantity"] // max_in_row)
        leftover = load["quantity"] % max_in_row

        total_ldm += num_rows * ldm_per_row
        if leftover > 0:
            total_ldm += ldm_per_row * (leftover / max_in_row)

    return round(total_ldm, 2)


def determine_vehicle(ldm):
    if ldm <= VEHICLES["bus"]["length"] / 100:
        return "bus"
    elif ldm <= VEHICLES["solowka"]["length"] / 100:
        return "solowka"
    elif ldm <= VEHICLES["naczepa"]["length"] / 100:
        return "naczepa"
    return "ponadgabaryt"

distance_tool = Tool(
    name="distance_tool",
    func=lambda x: estimate_distance(*x.split("->")) if "->" in x else None,
    description="Oblicza odległość między dwoma kodami pocztowymi w formacie '12345->67890'",
)

ldm_tool = Tool(
    name="ldm_tool",
    func=lambda x: calculate_ldm(json.loads(x)),
    description="Oblicza metry ładunkowe (LDM) dla zestawu ładunków przekazanego w formacie JSON."
)

json_extraction_tool = Tool(
    name="json_extraction_tool",
    func=lambda x: json.dumps({
        "loads": [{"quantity": 0, "height": 0, "width": 0, "length": 0}],
        "pickup_postal_code": "",
        "delivery_postal_code": "",
        "pickup_date": "",
        "delivery_date": "",
        "pickup_days": "",
        "delivery_days": "",
        "vehicle_type": ""
    }),
    description="Generuje pusty szablon JSON do uzupełnienia danymi transportowymi."
)

def extract_transport_details(user_input):
    agent = initialize_agent(
        tools=[distance_tool, ldm_tool, json_extraction_tool],
        llm=llm,
        agent=AgentType.ZERO_SHOT_REACT_DESCRIPTION,
        verbose=True
    )

    with Progress(SpinnerColumn(), TextColumn("[bold blue]Agent analizuje zapytanie..."), transient=True) as progress:
        task = progress.add_task("", total=None)
        response = agent.run(f"Analizuj to zapytanie i zwróć dane w formacie JSON: {user_input}")
        progress.update(task, completed=100)

    console.log(f"[green]Agent zwrócił wynik:[/green] {response}")
    return response

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/process', methods=['POST'])
def process():
    user_query = request.json.get("query")

    console.log("[yellow]Rozpoczynam przetwarzanie zapytania...[/yellow]")
    
    response = extract_transport_details(user_query)
    data = json.loads(response) if response else None

    if not data:
        console.log("[red]Błąd: Nie udało się przetworzyć zapytania.[/red]")
        return jsonify({"error": "Nie udało się przetworzyć zapytania."}), 400

    validation_error = validate_cargo_dimensions(data["loads"])
    if validation_error:
        console.log(f"[red]Błąd walidacji:[/red] {validation_error['error']}")
        return jsonify(validation_error), 400

    with Progress(SpinnerColumn(), TextColumn("[bold green]Obliczanie odległości..."), transient=True) as progress:
        task = progress.add_task("", total=None)
        data['distance_km'] = estimate_distance(data['pickup_postal_code'], data['delivery_postal_code'])
        progress.update(task, completed=100)

    with Progress(SpinnerColumn(), TextColumn("[bold green]Obliczanie LDM..."), transient=True) as progress:
        task = progress.add_task("", total=None)
        data["ldm"] = calculate_ldm(data["loads"])
        progress.update(task, completed=100)

    data["vehicle_type"] = determine_vehicle(data["ldm"])

    console.log(f"[cyan]Gotowy wynik:[/cyan] {json.dumps(data, indent=4)}")

    return jsonify(data)

if __name__ == '__main__':
    app.run(debug=True)
