# distance_tool.py
import requests
from langchain.agents import Tool


def get_postal_code_from_city(city: str) -> str:
    """
    Pobiera kod pocztowy na podstawie nazwy miasta, korzystając z OpenStreetMap (OSM).
    """
    lat, lon = geocode_address(city)
    
    if lat is None or lon is None:
        return ""

    # 🔹 Pobieranie kodu pocztowego na podstawie współrzędnych
    url = f"https://nominatim.openstreetmap.org/reverse?lat={lat}&lon={lon}&format=json"
    headers = {"User-Agent": "TransportAgent/1.0"}
    
    try:
        response = requests.get(url, headers=headers)
        data = response.json()

        if "address" in data and "postcode" in data["address"]:
            return data["address"]["postcode"]
    except:
        pass

    return ""

def geocode_address(address: str) -> tuple[float, float]:
    """
    Zwraca (latitude, longitude) dla podanego adresu/miasta
    korzystając z Nominatim (OpenStreetMap).
    """
    url = "https://nominatim.openstreetmap.org/search"
    params = {
        "q": address,
        "format": "json",
        "limit": 1
    }
    headers = {
        "User-Agent": "MojaAplikacja/1.0 (kontakt@twojadomena.pl)"
    }
    response = requests.get(url, params=params, headers=headers)
    data = response.json()

    if not data:
        return (None, None)

    try:
        lat = float(data[0]["lat"])
        lon = float(data[0]["lon"])
        return (lat, lon)
    except (KeyError, ValueError, IndexError):
        return (None, None)


def get_osrm_distance(lat1: float, lon1: float, lat2: float, lon2: float) -> float:
    """
    Korzysta z publicznego OSRM do obliczenia dystansu (w km)
    między dwoma punktami.
    """
    base_url = "https://router.project-osrm.org/route/v1/driving"
    url = f"{base_url}/{lon1},{lat1};{lon2},{lat2}"
    params = {
        "overview": "false"
    }
    headers = {
        "User-Agent": "MojaAplikacja/1.0 (kontakt@twojadomena.pl)"
    }

    try:
        r = requests.get(url, params=params, headers=headers)
        data = r.json()
        if "routes" not in data or not data["routes"]:
            return -1.0

        dist_meters = data["routes"][0]["distance"]
        dist_km = dist_meters / 1000.0
        return dist_km
    except:
        return -1.0


def get_distance_osm(origin: str, destination: str) -> float:
    """
    Zwraca dystans w km między origin a destination,
    korzystając z Nominatim (geokodowanie) + OSRM (liczenie trasy).
    """
    lat1, lon1 = geocode_address(origin)
    lat2, lon2 = geocode_address(destination)

    if lat1 is None or lon1 is None or lat2 is None or lon2 is None:
        return -1.0

    dist_km = get_osrm_distance(lat1, lon1, lat2, lon2)
    return dist_km


def distance_tool(input_text: str) -> str:
    """
    Oczekuje inputu w formacie "Origin->Destination", np. "Warszawa->Kraków".
    Zwraca tekst o wyliczonej odległości.
    """
    parts = input_text.split("->")
    if len(parts) != 2:
        return "Niepoprawny format. Użyj np. 'Warszawa->Kraków'."

    origin = parts[0].strip()
    destination = parts[1].strip()

    dist_km = get_distance_osm(origin, destination)
    if dist_km < 0:
        return "Nie udało się obliczyć dystansu. Sprawdź czy nazwy miejscowości są prawidłowe."

    return f"Odległość z {origin} do {destination} to około {dist_km:.1f} km."


