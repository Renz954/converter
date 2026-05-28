
<!DOCTYPE html>
<html>
<head>
    <title>Dormitory Module - Convert PDF SAN to Excel</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body>

<div class="container" style="margin-top:40px;">

    <div class="panel panel-primary">
        <div class="panel-heading">
            <h4 class="panel-title">Dormitory Module - PDF SAN Extractorsssss</h4>
        </div>

        <div class="panel-body">

            <p>
                Click the button below to scan the uploaded PDF and extract all numbers
                under the first <strong>SAN</strong> column into an Excel file.
            </p>

            <form method="post" action="<?php echo base_url('index.php/pdf_converter/convert_to_excel'); ?>">
                <button type="submit" class="btn btn-success">
                    <i class="glyphicon glyphicon-download-alt"></i>
                    Convert to Excel File
                </button>
            </form>

        </div>
    </div>

</div>

</body>
</html>