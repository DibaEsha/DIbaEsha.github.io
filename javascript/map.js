// Wait for the entire HTML document to be fully loaded and parsed
document.addEventListener('DOMContentLoaded', function() {

    // --- 1. DEFINE CONSTANTS ---
    const mapFrame = document.getElementById("googleMap");
    const originButton = document.getElementById("btnOrigin");
    const habitatButton = document.getElementById("btnHabitat");

    // Updated URLs for Satellite Hybrid view
    const worldMapUrl = "https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d34346452.75681648!2d-2.437500000000032!3d49.88750107699413!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e1!3m2!1sen!2sca!4v1729445258869!5m2!1sen!2sca";
    const bangladeshMapUrl = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d7511447.868158572!2d87.97011033664864!3d23.77189136199496!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30635ea8f7d3159f%3A0x8a727b68b08f86f!2sBangladesh!5e1!3m2!1sen!2sca!4v1729444858066!5m2!1sen!2sca";
    const canadaMapUrl = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d27320442.92386121!2d-114.3567812450284!3d59.96349098734008!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4b0d03d337cc6ad9%3A0x9968b72aa2438fa5!2sCanada!5e1!3m2!1sen!2sca!4v1729444895015!5m2!1sen!2sca";

    // --- 2. STATE VARIABLE ---
    // This variable will hold the URL of the map that is "stuck"
    let stickyMapUrl = worldMapUrl;

    // --- 3. EVENT LISTENERS ---

    // Set the sticky URL when a button is clicked (works for mouse and tap)
    originButton.addEventListener('click', function() {
        stickyMapUrl = bangladeshMapUrl;
        mapFrame.src = stickyMapUrl;
    });

    habitatButton.addEventListener('click', function() {
        stickyMapUrl = canadaMapUrl;
        mapFrame.src = stickyMapUrl;
    });

    // --- Hover effects for desktop ---

    // On mouse entering the button, show the temporary map
    originButton.addEventListener('mouseenter', function() {
        mapFrame.src = bangladeshMapUrl;
    });
     habitatButton.addEventListener('mouseenter', function() {
        mapFrame.src = canadaMapUrl;
    });

    // On mouse leaving the button, revert to the sticky map
    originButton.addEventListener('mouseleave', function() {
        mapFrame.src = stickyMapUrl;
    });
    habitatButton.addEventListener('mouseleave', function() {
        mapFrame.src = stickyMapUrl;
    });
    
     // --- Touch events for mobile to mimic hover ---

    // When a touch starts, show the temporary map
    originButton.addEventListener('touchstart', function(event) {
        event.preventDefault(); // Prevents the browser from also firing a click event
        mapFrame.src = bangladeshMapUrl;
    });

    habitatButton.addEventListener('touchstart', function(event) {
        event.preventDefault();
        mapFrame.src = canadaMapUrl;
    });

    // When a touch ends, revert to the sticky map
    originButton.addEventListener('touchend', function(event) {
        event.preventDefault();
        mapFrame.src = stickyMapUrl;
    });
    
    habitatButton.addEventListener('touchend', function(event) {
        event.preventDefault();
        mapFrame.src = stickyMapUrl;
    });


});

