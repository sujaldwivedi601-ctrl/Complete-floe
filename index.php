<!DOCTYPE html>
<html>
<head>
<title>Upload PDF</title>
</head>

<body>

<h2>Upload PDF</h2>

<form action="readpdf.php" method="POST" enctype="multipart/form-data">

<input type="file" name="pdf_file" accept="application/pdf" required>

<br><br>

<button type="submit">Upload</button>

</form>

</body>
</html>