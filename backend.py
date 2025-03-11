import os
import json
from dotenv import load_dotenv
from flask import Flask, request, jsonify, render_template
from langchain.chat_models import ChatOpenAI
from langchain.agents import AgentType, initialize_agent, Tool
from rich.console import Console
from calculateDate import calculate_date
from calculateLDM import calculate_ldm
from estimateDistance import distance_tool
from datetime import datetime

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


distance_tool = Tool(
    name="distance_tool",
    func=distance_tool,
    description=(
        "Oblicza odległość w km między dwoma lokalizacjami, "
        "podanymi w formacie 'MiastoA->MiastoB'."
        "Zwraca opis a w nim odległość w kilometrach"
    )
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
    today_date = datetime.now().strftime("%d.%m.%Y")  # Pobierz dzisiejszą datę

    console.log(f"[yellow]Rozpoczynam przetwarzanie zapytania... Dzisiejsza data: {today_date}[/yellow]")

    agent = initialize_agent(
        tools=[distance_tool, ldm_tool, json_extraction_tool],
        llm=llm,
        agent=AgentType.ZERO_SHOT_REACT_DESCRIPTION,
        verbose=True
    )
 
 
    response = agent.run(f"Dzisiejsza data to {today_date}. Analizuj to zapytanie i zwróć dane w formacie JSON: {user_query}")
   
    data = json.loads(response) if response else None
    
    console.log(json.dumps(data, indent=4, ensure_ascii=False))

    if not data:
        console.log("[red]Błąd: Nie udało się przetworzyć zapytania.[/red]")
        return jsonify({"error": "Nie udało się przetworzyć zapytania."}), 400

    data["ldm"] = calculate_ldm(data["loads"])

    # Formatowanie dat
    data["pickup_date"] = calculate_date(data["pickup_days"])
    data["delivery_date"] = calculate_date(data["delivery_days"])  # Domyślnie dzień później

    # Stałe wartości (pojazd zawsze naczepa)
    data["vehicle_type"] = VEHICLE_TYPE

    return jsonify(data)

if __name__ == '__main__':
    app.run(debug=True, threaded=True)
