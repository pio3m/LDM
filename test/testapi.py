import requests

# Ustawienie klucza API
api_key = "AIzaSyCXsY_1u6aT39MPEZ-xPvLayAXbEDx67V8"

# Adres URL punktu końcowego API
endpoint = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={api_key}"

# Przykładowy JSON z treścią do wygenerowania
data = {
    "contents": [
        {
            "parts": [
                {"text": "Napisz krótki opis o AI:"}
            ]
        }
    ]
}

# Wykonanie żądania POST
response = requests.post(endpoint, json=data)

# Wyświetlenie odpowiedzi
print(response.json())
