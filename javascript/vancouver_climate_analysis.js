document.addEventListener("DOMContentLoaded", () => {
    // --- 1. Initialize Map ---
    const map = L.map('vancouverMap').setView([49.25, -123.12], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // --- Helper: CSV Parser ---
    function parseCSV(csvText) {
        const lines = csvText.trim().split('\n');
        const headers = lines[0].trim().split(',');
        
        return lines.slice(1).map(line => {
            const values = line.split(','); // Simple split (assumes no commas in fields)
            // If your CSV has quoted fields with commas, this simple split might fail.
            // But standard climate data is usually clean numbers/text.
            
            const obj = {};
            headers.forEach((header, index) => {
                // Clean quotes if present
                let val = values[index] ? values[index].trim() : '';
                if (val.startsWith('"') && val.endsWith('"')) {
                    val = val.substring(1, val.length - 1);
                }
                obj[header] = val;
            });
            return obj;
        });
    }

    // --- 2. Fetch CSV Data ---
    fetch('../data/climate-normals.csv')
        .then(response => {
            if (!response.ok) throw new Error("CSV not found");
            return response.text();
        })
        .then(csvText => {
            const data = parseCSV(csvText);
            
            // Debug: Check first row to verify column names
            // console.log("First row:", data[0]);

            // --- Map: Plot Stations ---
            const plottedStations = new Set();
            
            // Filter for unique stations to plot
            data.forEach(row => {
                const stationID = row.CLIMATE_IDENTIFIER;
                // Ensure we have coordinates
                if (row.x && row.y && !plottedStations.has(stationID)) {
                    const lat = parseFloat(row.y);
                    const lng = parseFloat(row.x);
                    
                    if (!isNaN(lat) && !isNaN(lng)) {
                        L.marker([lat, lng])
                            .addTo(map)
                            .bindPopup(`<b>${row.STATION_NAME}</b><br>ID: ${stationID}`)
                            .openPopup();
                        plottedStations.add(stationID);
                    }
                }
            });

            // --- Data Processing for Charts ---
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            
            // Initialize arrays for data
            let avgPrecipitation = new Array(12).fill(0);
            let avgMeanMaxTemp = new Array(12).fill(0); // For Table & Line Chart (Highs)
            let avgMeanTemp = new Array(12).fill(0);    // For Combined Chart (Averages)
            
            // Initialize counters for averaging
            let countPrecip = new Array(12).fill(0);
            let countMeanMax = new Array(12).fill(0);
            let countMean = new Array(12).fill(0);

            data.forEach(row => {
                // Parse Month (Assuming 'MONTH' column exists and is 1-13)
                const monthIndex = parseInt(row.MONTH) - 1; 
                if (monthIndex < 0 || monthIndex > 11) return; 

                const val = parseFloat(row.VALUE);
                // Validate value
                if (isNaN(val) || val === null || val < -100 || val > 10000) return;

                const elementName = row.E_NORMAL_ELEMENT_NAME ? row.E_NORMAL_ELEMENT_NAME.trim().toLowerCase() : '';

                // 1. Total Precipitation
                if (elementName.includes("total precipitation") || elementName.includes("total rainfall")) {
                    avgPrecipitation[monthIndex] += val;
                    countPrecip[monthIndex]++;
                }

                // 2. Mean Daily Max Temperature (Highs) - For Table
                // Matches "Mean daily max temperature deg C." or "Daily maximum temperature"
                if (elementName.includes("mean daily max temperature") || elementName.includes("daily maximum temperature")) {
                    avgMeanMaxTemp[monthIndex] += val;
                    countMeanMax[monthIndex]++;
                }

                // 3. Mean Daily Temperature (Average) - For Combined Chart
                // Matches "Mean daily temperature deg C." or "Daily average temperature"
                // Explicitly check it's NOT max or min to be safe, though specific strings usually suffice
                if ((elementName.includes("mean daily temperature") || elementName.includes("daily average temperature")) && !elementName.includes("max") && !elementName.includes("min")) {
                    avgMeanTemp[monthIndex] += val;
                    countMean[monthIndex]++;
                }
            });

            // Calculate Averages
            for (let i = 0; i < 12; i++) {
                if (countPrecip[i] > 0) avgPrecipitation[i] /= countPrecip[i];
                if (countMeanMax[i] > 0) avgMeanMaxTemp[i] /= countMeanMax[i];
                if (countMean[i] > 0) avgMeanTemp[i] /= countMean[i];
            }
            
            // --- Chart 1: Bar Chart - Precipitation (Jan-Jun) ---
            const ctxPrecip = document.getElementById('precipChart').getContext('2d');
            new Chart(ctxPrecip, {
                type: 'bar',
                data: {
                    labels: months.slice(0, 6), 
                    datasets: [{
                        label: 'Avg Precipitation (mm)',
                        data: avgPrecipitation.slice(0, 6),
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true, title: {display: true, text: 'Precipitation (mm)'} } }
                }
            });

            // --- Chart 2: Line Chart - Daily High Temp (All Months) ---
            // Uses Mean Max Temp (Highs)
            const ctxTemp = document.getElementById('tempLineChart').getContext('2d');
            new Chart(ctxTemp, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Avg Daily High Temp (°C)',
                        data: avgMeanMaxTemp,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: false, title: {display: true, text: 'Temperature (°C)'} } }
                }
            });

            // --- Chart 3: Combined Chart (Bar + Line) ---
            // Uses Precipitation (Bar) and Mean Daily Temp (Line)
            const ctxCombined = document.getElementById('combinedChart').getContext('2d');
            new Chart(ctxCombined, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Precipitation (mm)',
                            data: avgPrecipitation,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            yAxisID: 'y'
                        },
                        {
                            type: 'line',
                            label: 'Mean Daily Temp (°C)',
                            data: avgMeanTemp,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderWidth: 2,
                            yAxisID: 'y1',
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {display: true, text: 'Precipitation (mm)'}
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {display: true, text: 'Temperature (°C)'},
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });

            // --- Table Population ---
            // Uses Mean Max Temp (Highs)
            const tableBody = document.querySelector('#tempTable tbody');
            tableBody.innerHTML = ""; // Clear existing rows
            months.forEach((month, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${month}</td>
                    <td>${avgMeanMaxTemp[index].toFixed(1)}</td>
                `;
                tableBody.appendChild(row);
            });

        })
        .catch(err => console.error("Error processing climate data:", err));
});