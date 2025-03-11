import os
import json
import datetime
import requests
from dotenv import load_dotenv
from flask import Flask, request, jsonify, render_template
from langchain.chat_models import ChatOpenAI
from langchain.agents import AgentType, initialize_agent, Tool
from rich.console import Console
from calculateDate import calculate_date
from calculateLDM import calculate_ldm

load_dotenv()

console = Console()
app = Flask(__name__, template_folder="templates")

# Konfiguracja AI
llm = ChatOpenAI(model_name="gpt-4", openai_api_key=os.getenv("OPENAI_API_KEY"))

# Stałe parametry dla naczepy
VEHICLE_TYPE = "naczepa"
VEHICLE_WIDTH = 240
VEHICLE_HEIGHT = 260
VEHICLE_LENGTH = 1360

def estimate_distance(postal_code_from, postal_code_to):
    """Oblicza odległość w km między dwoma kodami pocztowymi"""
    url = f"https://router.project-osrm.org/route/v1/driving/{postal_code_from};{postal_code_to}"
    headers = {"User-Agent": "TransportApp/1.0"}
    response = requests.get(url, headers=headers)
    data = response.json()
    return round(data["routes"][0]["distance"] / 1000.0, 2) if "routes" in data and data["routes"] else -1.0

distance_tool = Tool(
    name="distance_tool",
    func=lambda x: estimate_distance(*x.split("->")) if "->" in x else None,
    description="Oblicza odległość między dwoma kodami pocztowymi w formacie '12345->67890'."
)

ldm_tool = Tool(
    name="ldm_tool",
    func=lambda x: calculate_ldm(json.loads(x)),
    description="Oblicza metry ładunkowe (LDM) dla zestawu ładunków przekazanego w formacie JSON."
)

json_extraction_tool = Tool(
    name="json_extraction_tool",
    func=lambda x: json.dumps({
        "loads": [{"quantity": 0, "width": 0, "length": 0}],
        "pickup_postal_code": "",
        "delivery_postal_code": "",
        "pickup_date": "",
        "delivery_date": "",
        "pickup_days": "",
        "delivery_days": "",
        "distance_km":""
    }),
    description="Generuje pusty szablon JSON do uzupełnienia danymi transportowymi."
)

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/process', methods=['POST'])
def process():
    user_query = request.json.get("query")

    console.log("[yellow]Rozpoczynam przetwarzanie zapytania...[/yellow]")

    agent = initialize_agent(
        tools=[distance_tool, ldm_tool, json_extraction_tool],
        llm=llm,
        agent=AgentType.ZERO_SHOT_REACT_DESCRIPTION,
        verbose=True
    )
    
    response = agent.run(f"Analizuj to zapytanie i zwróć dane w formacie JSON: {user_query}")
    data = json.loads(response) if response else None
    
    console.log(json.dumps(data, indent=4, ensure_ascii=False))

    if not data:
        console.log("[red]Błąd: Nie udało się przetworzyć zapytania.[/red]")
        return jsonify({"error": "Nie udało się przetworzyć zapytania."}), 400

    data["distance_km"] = estimate_distance(data["pickup_postal_code"], data["delivery_postal_code"])
    
    data["ldm"] = calculate_ldm(data["loads"])

    # Formatowanie dat
    data["pickup_date"] = calculate_date(data["pickup_days"])
    data["delivery_date"] = calculate_date(data.get("delivery_days", 1))  # Domyślnie dzień później

    # Stałe wartości (pojazd zawsze naczepa)
    data["vehicle_type"] = VEHICLE_TYPE

    return jsonify(data)

if __name__ == '__main__':
    app.run(debug=True, threaded=True)
