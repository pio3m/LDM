document.getElementById("submit").addEventListener("click", function() {
    const prompt = document.getElementById("prompt").value;
    const resultDiv = document.getElementById("result");
    const loader = document.getElementById("loader");
    const toggleButton = document.getElementById("toggle-json");

    resultDiv.textContent = "";
    loader.classList.remove("d-none");
    toggleButton.classList.add("d-none");
    resultDiv.classList.add("d-none");

    fetch("/process", {
        method: "POST",
        body: JSON.stringify({ prompt }),
        headers: {
            "Content-Type": "application/json"
        }
    })
    .then(response => response.json())
    .then(data => {
        loader.classList.add("d-none");
        console.log("Otrzymany JSON:", data);

        resultDiv.textContent = JSON.stringify(data, null, 4);
        toggleButton.classList.remove("d-none");

        if (data.loads && data.loads.length > 0) {
            document.getElementById("height").value = data.loads[0].height || data.loads[0].wysokosc || "";
            document.getElementById("width").value = data.loads[0].width || data.loads[0].szerokosc || "";
            document.getElementById("length").value = data.loads[0].length || data.loads[0].dlugosc || "";
            document.getElementById("quantity").value = data.loads[0].quantity || data.loads[0].ilosc || "";
            document.getElementById("weight").value = data.loads[0].weight || data.loads[0].waga || "";
        }
        document.getElementById("pickup_date").value = data.pickup_date || "";
        document.getElementById("delivery_date").value = data.delivery_date || "";
        document.getElementById("pickup_postal_code").value = data.pickup_postal_code || "";
        document.getElementById("delivery_postal_code").value = data.delivery_postal_code || "";
        document.getElementById("distance_km").value = data.distance_km || "";
        document.getElementById("ldm").value = data.ldm || "";
    })
    .catch(error => {
        loader.classList.add("d-none");
        console.error("Błąd podczas pobierania JSON:", error);
    });
});
