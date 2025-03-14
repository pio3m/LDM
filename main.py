from flask import Flask, render_template, request, jsonify
from langchain.chat_models import ChatOpenAI
from langchain.schema import SystemMessage, HumanMessage
import json
import datetime
import os
from estimateDistance import get_distance_osm, get_postal_code_from_city
from calculateLDM import calculate_ldm
from dotenv import load_dotenv

app = Flask(__name__)

load_dotenv()
llm = ChatOpenAI(model="gpt-4", temperature=0, openai_api_key=os.getenv("OPENAI_API_KEY"))

def process_prompt(prompt):
    """Agent analizuje prompt i uzupełnia JSON tylko jeśli jest pewien"""
    
    system_message = SystemMessage(content="""
    Jesteś asystentem do analizy transportu.
    Twoim zadaniem jest wypełnienie poniższego JSON na podstawie treści zapytania.

    Struktura JSON:
    {
        "loads": [{"quantity": 0, "width": 0, "length": 0}],
        "pickup_postal_code": "",
        "delivery_postal_code": "",
        "pickup_city":"",
        "delivery_city":"",
        "pickup_date": "",
        "delivery_date": "",
        "distance_km": "",
        "ldm": ""
    }

    Zasady:
    - Jeśli znajdziesz ilość palet, szerokość, długość - uzupełnij je.
    - Jeśli znajdziesz kod pocztowy dla miejsca odbioru i dostawy - uzupełnij.
    - Jeśli znajdziesz termin odbioru i dostawy - zamień go na YYYY-MM-DD.
    - Jeśli nie masz pewności co do wartości, zostaw pole jako pusty string.
    """)

    user_message = HumanMessage(content=prompt)
    
    response = llm([system_message, user_message]).content

    # Spróbuj sparsować JSON, jeśli AI zwróci coś błędnego, zwróć pusty JSON
    try:
        parsed_json = json.loads(response)
    except json.JSONDecodeError:
        parsed_json = {
            "loads": [{"quantity": 0, "width": 0, "length": 0}],
            "pickup_postal_code": "",
            "delivery_postal_code": "",
            "pickup_city":"",
            "delivery_city":"",
            "pickup_date": "",
            "delivery_date": "",
            "distance_km": "",
            "lmd": ""
        }
    # 🔹 Jeśli brak kodu pocztowego odbioru, pobierz go z miasta
    if not parsed_json.get("pickup_postal_code") and parsed_json.get("pickup_city"):
        parsed_json["pickup_postal_code"] = get_postal_code_from_city(parsed_json["pickup_city"])

    # 🔹 Jeśli brak kodu pocztowego dostawy, pobierz go z miasta
    if not parsed_json.get("delivery_postal_code") and parsed_json.get("delivery_city"):
        parsed_json["delivery_postal_code"] = get_postal_code_from_city(parsed_json["delivery_city"])

    if parsed_json.get("loads"):
        ldm_value = calculate_ldm(parsed_json["loads"])
        if ldm_value >= 0:
            parsed_json["ldm"] = f"{ldm_value:.1f} LDM"

    if parsed_json.get("pickup_postal_code") and parsed_json.get("delivery_postal_code"):
        origin = parsed_json["pickup_postal_code"]
        destination = parsed_json["delivery_postal_code"]
        distance_km = get_distance_osm(origin, destination)

        # Jeśli funkcja zwróciła poprawną odległość, uzupełniamy wartość
        if distance_km >= 0:
            parsed_json["distance_km"] = distance_km

    return parsed_json

@app.route("/")
def index():
    """Wyświetlenie strony głównej"""
    return render_template("index.html")

@app.route("/process", methods=["POST"])
def process():
    """Przetwarzanie prompta i zwracanie JSON"""
    data = request.json
    prompt = data.get("prompt", "")

    if not prompt.strip():
        return jsonify({"error": "Brak danych w zapytaniu"}), 400

    result = process_prompt(prompt)
    return jsonify(result)

if __name__ == '__main__':
    app.run(host="0.0.0.0", port=int(os.getenv("PORT", 5000)), debug=True)