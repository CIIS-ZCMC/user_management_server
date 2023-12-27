<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        @page {

            /* Set the page size to auto to adjust based on content */
            margin: 0 !important;
            padding: 0 !important;
        }

        body {
            margin: 0;
            /* Remove any margin on the body */
            padding: 0;
            /* Remove any padding on the body */
        }

        #tbleformat {

            /* Make the table width 100% of the page */
            margin-left: 1.4px;
            border-collapse: collapse;
            /* Collapse table borders to remove spacing */
        }

        #tbleformat tr td {
            padding: 0;
            margin: 0;

            /* Add borders for demonstration purposes */
        }
    </style>
</head>

<body>


    <table id="tbleformat">
        <tr>
            <td>
                @include('leave_from.leave_format')
            </td>
            <td>

            </td>
        </tr>
    </table>


</body>

</html>
