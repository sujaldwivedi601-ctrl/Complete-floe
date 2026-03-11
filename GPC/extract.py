from flask import Flask, request, jsonify
from flask_cors import CORS
import pdfplumber

app = Flask(__name__)
CORS(app)

@app.route("/")
def home():
    return "Server Running"

@app.route("/upload", methods=["POST"])
def upload_pdf():

    semester = request.form.get("semester")
    file = request.files["pdf"]

    subjects = []

    with pdfplumber.open(file) as pdf:

        page = pdf.pages[0]
        table = page.extract_table()

        for row in table:

            if row and len(row) > 10:

                subject = row[3]
                theory = row[4]
                practical = row[12]

                if subject and subject != "SUBJECTNAME":

                    subjects.append({
                        "semester": semester,
                        "subject": subject,
                        "theory": theory,
                        "practical": practical
                    })

    return jsonify(subjects)

if __name__ == "__main__":
    app.run(debug=True)