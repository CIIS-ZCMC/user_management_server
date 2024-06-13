<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="shortcut icon" href="{{ asset('storage/logo/zcmc.jpeg') }}" type="image/x-icon">
    <title>UMIS-Biometric Logs</title>
</head>
<style>
    body {
        padding: 10px;
        margin: 0;
        text-align: center !important;
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%,-50%);
    }

    #txtempIDfield{
        padding: 40px;
        width:450px;
        font-size: 44px;
        text-align: center;
    }

    #btncheck{
        margin-top: 20px;
        padding: 20px 80px;
        font-size:20px;
        background-color: #5D9C59;
        color:white;
      
        outline: none;
        border-radius: 10px;
        transition: all ease-in-out   .2s;
        border:1px solid transparent;
    }

    #btncheck:hover{
        background-color: #3d8538;
        border:1px solid white;
    }

    #btncheck.clicked {
    background-color: #2E7D32; /* Darker green or any color you want */
    border: 1px solid #2E7D32; /* Darker border or any color you want */
    color: yellow; /* Change text color if needed */
}



</style>
<body>
 
    <img src="{{ asset('storage/logo/zcmc.jpeg') }}" style="width: 220px;user-select: none" alt="">
    <h1 style="user-select: none" > <span style="color: #006989">
        UMIS <br>  DTR & Biometric log Checker    
        <br>
        <div style="font-size:40px;color:green">   {{date('F j,Y')}}  <span id="server-time">{{date('H:i:s')}}</span></div> 
    </span> 

<span style="font-size:25px;font-weight:normal">
    Check your biometric logs or DTR here!
</span>
    </h1>

    <form action="{{route("check.logs")}}" id="checklogsform" method="POST">
        @csrf
        <input type="text" required class="active-input" name="employee_ID" id="txtempIDfield" autofocus placeholder="Enter Employee ID" onkeypress="return (event.charCode >= 48 && event.charCode <= 57)"  onkeydown="if (event.keyCode == 13) handleEnterKey(event)">
        <br>
        <button type="button" id="btncheck"> CHECK</button>
    </form>
    <br><br>
    <span style="font-size:13px;font-weight:normal">User Management Information System</span>
  

  
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>

function handleEnterKey(event) {
            event.preventDefault();
         $('#btncheck').click()
        }
    $(document).ready(function() {
    
    $('#btncheck').on('click', function() {
      var val = $('#txtempIDfield').val()
     if(val){
        $('#btncheck').addClass('clicked');
        $('#btncheck').html("Checking ..");
        setTimeout(() => {
            $('#checklogsform').submit()
        }, 1500);
        return
      }

   

    });


    function formatTime(hours, minutes, seconds) {
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    // Function to get server time
    function getServerTime() {
        const currentTime = new Date();
        const hours = currentTime.getHours();
        const minutes = currentTime.getMinutes();
        const seconds = currentTime.getSeconds();
        return formatTime(hours, minutes, seconds);
    }

    // Update server time every second
    const interval = setInterval(() => {
        const currentTime = getServerTime();
        $('#server-time').text(currentTime); // Update server time display
    }, 1000);


});

</script>
</body>
</html>