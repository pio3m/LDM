from rich.console import Console
import json

console = Console()
VEHICLE_WIDTH = 240

def calculate_ldm(loads):
    """
    Oblicza zajętą długość pojazdu (LDM) na podstawie podanych ładunków.
    :param loads: Lista słowników zawierających 'length', 'width' i 'quantity'.
    :return: Całkowita wartość LDM.
    """
    if isinstance(loads, str):
        try:
            loads = json.loads(loads)
            if isinstance(loads, dict) and "loads" in loads:
                loads = loads["loads"]
        except json.JSONDecodeError:
            console.log("[red]Błąd: Nieprawidłowy format danych wejściowych w calculate_ldm.[/red]")
            return -1
    
    if not isinstance(loads, list):
        console.log("[red]Błąd: Nieprawidłowy format ładunków. Oczekiwano listy.[/red]")
        return -1

    total_ldm = 0
    remaining_width = VEHICLE_WIDTH
    regular_loads = []

    for load in loads:
        if not isinstance(load, dict):
            console.log("[red]Błąd: Nieprawidłowy format pojedynczego ładunku.[/red]")
            return -1
        
        length = load.get('length', 0) * 100  # z metrów na cm
        width = load.get('width', 0) * 100

        quantity = load.get('quantity', 1)
        
        if max(length, width) > VEHICLE_WIDTH:
            total_ldm += (max(length, width) / 100) * quantity
            remaining_width -= min(length, width)
        else:
            regular_loads.append((length, width, quantity))

    while regular_loads:
        length, width, quantity = regular_loads.pop(0)
        fit_by_length = VEHICLE_WIDTH // length if length > 0 else 1
        fit_by_width = VEHICLE_WIDTH // width if width > 0 else 1

        if fit_by_length > fit_by_width:
            units_per_row = fit_by_length
            ldm_value = width / 100
        else:
            units_per_row = fit_by_width
            ldm_value = length / 100

        full_rows = quantity // units_per_row
        remaining_units = quantity % units_per_row

        total_ldm += full_rows * ldm_value

        if remaining_units > 0:
            if remaining_units * max(length, width) <= VEHICLE_WIDTH:
                total_ldm += (min(length, width) / 100)
            else:
                total_ldm += (max(length, width) / 100)

    return round(total_ldm, 1)