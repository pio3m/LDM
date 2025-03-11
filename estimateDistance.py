import requests

def estimate_distance(postal_code_from, postal_code_to):
    """Oblicza odległość w km między dwoma kodami pocztowymi"""
    url = f"https://router.project-osrm.org/route/v1/driving/{postal_code_from};{postal_code_to}"
    headers = {"User-Agent": "TransportApp/1.0"}
    response = requests.get(url, headers=headers)
    data = response.json()
    return round(data["routes"][0]["distance"] / 1000.0, 2) if "routes" in data and data["routes"] else -1.0
