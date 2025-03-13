document.getElementById("submit").addEventListener("click", function() {
    const prompt = document.getElementById("prompt").value;
    const resultDiv = document.getElementById("result");
    const loader = document.getElementById("loader");

    resultDiv.textContent = "";
    loader.style.display = "block";

    fetch("/process", {
        method: "POST",
        body: JSON.stringify({ prompt }),
        headers: {
            "Content-Type": "application/json"
        }
    })
    .then(response => response.json())
    .then(data => {
        loader.style.display = "none";
        resultDiv.textContent = JSON.stringify(data, null, 4);
    })
    .catch(error => {
        loader.style.display = "none";
        resultDiv.textContent = "Błąd: " + error;
    });
});
