<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
                <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/5.5.2/css/foundation.css">

        <!-- jQuery library -->
        <script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>

        <!-- Latest compiled JavaScript -->
        <script src="http://cdnjs.cloudflare.com/ajax/libs/foundation/5.5.2/js/foundation.min.js"></script>

        <!-- Latest compiled modernizr -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>
	<title>Lechal</title>
</head>
<body>
    <div data-alert class="alert-box info" style="font-size: 24px;"> 
         Current maximum version available is <strong> <?php echo $maxVersion; ?> </strong>
    </div>
    <div class="large-4 medium-5 columns">
        <fieldset>
            <form action="#" method="post" enctype="multipart/form-data">
                New version value:
                <input type="text" name="newVersion">
                MS file:
                <input type="file" name="ms">
                QTR file:
                <input type="file" name="qtr">
                Change Log file:
                <input type="file" name="log">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="type" value="101">
                <button type="submit" class="button">Submit</button>
            </form> 
        </fieldset>
    </div>
</body>
</html>
