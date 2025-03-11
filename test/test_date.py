import pytest
from datetime import datetime, timedelta
from calculateDate import calculate_date  

def test_calculate_date_future():
    days = "10"
    expected_date = (datetime.now() + timedelta(days=10)).strftime("%d.%m.%Y")
    assert calculate_date(days) == expected_date

def test_calculate_date_past():
    days = "-5"
    expected_date = (datetime.now() + timedelta(days=-5)).strftime("%d.%m.%Y")
    assert calculate_date(days) == expected_date

def test_calculate_date_today():
    days = "0"
    expected_date = datetime.now().strftime("%d.%m.%Y")
    assert calculate_date(days) == expected_date

@pytest.mark.parametrize("days", [1, 7, 30, -1, -7, -30])
def test_calculate_date_various(days):
    expected_date = (datetime.now() + timedelta(days=days)).strftime("%d.%m.%Y")
    print(expected_date)
    assert calculate_date(days) == expected_date
