// Wait for the DOM to be fully loaded before running the script
document.addEventListener('DOMContentLoaded', function() {

    // --- 1. Date and Time Display ---
    const dateDisplay = document.getElementById('date-display');
    const timeDisplay = document.getElementById('time-display');

    function updateDateTime() {
        const now = new Date();

        // Format the date e.g., "September 20, 2025"
        const dateOptions = { year: 'numeric', month: 'long', day: 'numeric' };
        dateDisplay.textContent = now.toLocaleDateString('en-US', dateOptions);
        
        // Format the time e.g., "7:00:00 PM PDT"
        const timeOptions = { 
            hour: 'numeric', 
            minute: 'numeric', 
            second: 'numeric', 
            hour12: true,
            timeZoneName: 'short'
        };
        timeDisplay.textContent = now.toLocaleTimeString('en-US', timeOptions);
    }
    
    // Update the time every second
    updateDateTime(); // Initial call
    setInterval(updateDateTime, 1000);


    // --- 2. Change Text Color Button ---
    const changeColorBtn = document.getElementById('changeColorBtn');
    // The paragraph element whose color will change
    const colorChangeText = document.getElementById('colorChangeText'); 
    const colors = ['#e8491d', '#35424a', '#2ecc71', '#3498db', '#9b59b6', '#f1c40f'];
    let currentColorIndex = 0;

    changeColorBtn.addEventListener('click', function() {
        // Move to the next color in the array, looping back to the start
        currentColorIndex = (currentColorIndex + 1) % colors.length;
        
        const newColor = colors[currentColorIndex];
        // Change the color of the specified paragraph text
        colorChangeText.style.color = newColor; 
    });


    // --- 3. Image Hover Effect ---
    const hoverImage = document.getElementById('hoverImage');
    const originalImageSrc = 'BCIT_image.jpg';
    const hoverImageSrc = 'BCIT_campus.jpg';

    // Event listener for when the mouse pointer enters the image area
    hoverImage.addEventListener('mouseover', function() {
        hoverImage.src = hoverImageSrc;
    });

    // Event listener for when the mouse pointer leaves the image area
    hoverImage.addEventListener('mouseout', function() {
        hoverImage.src = originalImageSrc;
    });

});

