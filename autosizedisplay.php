<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Update Projection Text</title>
    <style>
        body {
            font-family: sans-serif;
            max-width: 600px;
            margin: 40px auto;
        }
        label {
            display: block;
            margin-top: 20px;
            font-weight: bold;
        }
        textarea {
            width: 100%;
            height: 120px;
            font-size: 1.1em;
        }
        button {
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 1.1em;
        }
    </style>
</head>
<body>

<h1>Update Live Projection</h1>
<?php $text = file_get_contents(__DIR__ . '/display/projectedtext.txt');?>



<form method="post" action="/display/updatetext.php">



    <label for="text">Text</label>
    <textarea name="text" id="text" placeholder="Enter new text here…">
	<?php 	echo $text; 	?>
	</textarea>

    <button type="submit">Update</button>

</form>

</body>
</html>
