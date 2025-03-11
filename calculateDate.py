from datetime import datetime, timedelta

def calculate_date(days):
    """Oblicza datę na podstawie liczby dni podanej jako liczba całkowita lub ciąg znaków."""
    # Sprawdzenie, czy days jest ciągiem znaków
    if not days:
        return ""
    
    if isinstance(days, str):
        try:
            # Konwersja ciągu znaków na liczbę całkowitą
            days = int(days)
        except ValueError:
            raise ValueError("Podano nieprawidłowy format liczby dni. Oczekiwano liczby całkowitej lub ciągu znaków reprezentującego liczbę całkowitą.")

    # Obliczanie daty
    today = datetime.now()
    return (today + timedelta(days=days)).strftime("%Y-%m-%d")
     