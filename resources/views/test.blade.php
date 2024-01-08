<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .invoice-receipt {
            max-width: 600px;
            margin: 0 auto;
            border: 2px solid #333;
            padding: 20px;
            border-radius: 10px;
        }

        .header {
            text-align: center;
            font-size: 1.5em;
            margin-bottom: 20px;
        }

        .shop-name {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .details {
            margin-bottom: 20px;
        }

        .details div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items th, .items td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }

        .total {
            font-weight: bold;
            text-align: right;
        }

        .payment-details {
            margin-top: 20px;
            border-top: 1px solid #333;
            padding-top: 10px;
        }
    </style>
    <title>Invoice Receipt</title>
</head>
<body>

    <div class="invoice-receipt">
        <div class="shop-name">AFIYAS FAST FOOD CORNER</div>
        <div class="header">Invoice Receipt</div>

        <div class="details">
            <div>
                <span>Order #:</span>
                <span>123456</span>
            </div>
            <div>
                <span>Ordered By:</span>
                <span>Your Company Name</span>
            </div>
            <div>
                <span>Customer Name:</span>
                <span>Client Name</span>
            </div>
            <div>
                <span>Invoice Date:</span>
                <span>December 1, 2023</span>
            </div>
            <div>
                <span>Due Date:</span>
                <span>December 15, 2023</span>
            </div>
            <div>
                <span>Discount:</span>
                <span>$25.00</span>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Product A</td>
                    <td>2</td>
                    <td>$50.00</td>
                    <td>$100.00</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Product B</td>
                    <td>1</td>
                    <td>$75.00</td>
                    <td>$75.00</td>
                </tr>
            </tbody>
        </table>

        <div class="total">Total: $150.00</div>

        <div class="payment-details">
            <div>
                <span>Payment Method:</span>
                <span>Credit Card</span>
            </div>
            <div>
                <span>Transaction ID:</span>
                <span>1234567890</span>
            </div>
        </div>
    </div>

</body>
</html>
