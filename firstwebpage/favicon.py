
from PIL import Image

# Load the PNG image
img = Image.open("chat_1042133.png")

# Save as ICO
img.save("favicon.ico", format="ICO")
