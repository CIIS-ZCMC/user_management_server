<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .Dtrview {
            display: block;
            width: 100%;
        }

        .Absent {
            background-color: #FFE5E5;
        }

        .Holiday {
            background-color: #FFD6A5;
        }

        .Present {
            background-color: #D2E3C8;
        }

        .wsched {
            background-color: rgb(150, 197, 150);
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
        integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>

<body>
    <div class="Dtrview">
        @include('dtr.DtrFormat')
    </div>
</body>

</html>
