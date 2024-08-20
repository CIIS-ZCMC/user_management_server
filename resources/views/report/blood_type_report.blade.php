<!DOCTYPE html>
<html lang="en">
<head>
  
    <style>

        .main-container {
            margin: 0px;
            padding: 0px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
            text-align: left;
        }

        table, th, td {
            border: 1px solid #dddddd;
        }

        th, td {
            padding: 12px;
        }

        th {
            background-color: #f2f2f2;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>  
    <div class="main-container">
        {{-- TOP CONTAINER --}}
        <table style="width: 100%" cellspacing="0" cellpadding="0">

            <tr>
                @foreach ($columns as $column)
                    <th>
                        {{ $column['headerName'] }} 
                    </th>
                @endforeach
            </tr>
        </table>
    </div>
</body>
</html>

