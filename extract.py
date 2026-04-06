from flask import Flask, request, jsonify
from flask_cors import CORS
import pdfplumber
import os

app = Flask(__name__)
# Enable CORS to allow your frontend to talk to this backend
CORS(app)

def clean_to_int(val):
    """
    Helper function to safely convert extracted table text to integers.
    Handles None, empty strings, and floating point strings.
    """
    try:
        if val is None:
            return 0
        return int(float(str(val).strip()))
    except (ValueError, TypeError):
        return 0

@app.route("/", methods=["GET"])
def home():
    return "Server is running. Send POST requests to /upload"

@app.route("/upload", methods=["POST"])
def upload_pdf():
    # 1. Get data from request
    semester = request.form.get("semester")
    file = request.files.get("pdf")
    
    if not file:
        return jsonify({"error": "No file uploaded"}), 400

    subjects = []

    # 2. Extract content from PDF
    try:
        with pdfplumber.open(file) as pdf:
            # Assuming the table is on the first page
            page = pdf.pages[0]
            table = page.extract_table()

            if not table:
                return jsonify({"error": "No table found in the PDF"}), 400

            # 3. Process each row
            for row in table:
                # Ensure the row has enough columns (e.g., at least 13 for indices 0-12)
                if row and len(row) > 12:
                    subject_name = str(row[3]).strip() if row[3] else ""
                    
                    # Skip header rows
                    if not subject_name or "SUBJECT" in subject_name.upper():
                        continue
                    
                    theory = clean_to_int(row[4])
                    practical = clean_to_int(row[12])

                    # Filter out empty entries
                    if theory == 0 and practical == 0:
                        continue

                    subjects.append({
                        "semester": semester,
                        "subject": subject_name,
                        "theory": theory,
                        "practical": practical
                    })
    except Exception as e:
        return jsonify({"error": f"Failed to process PDF: {str(e)}"}), 500

    return jsonify(subjects)

if __name__ == "__main__":
    # Run on default port 5000
    app.run(debug=True, port=5000)