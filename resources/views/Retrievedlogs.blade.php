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
        padding: 100px;
        margin: 0;

    }
    @media only screen and (max-width: 796px) {
        #containerD {
            width: 100%;
        }
    }

</style>
<body>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
$(document).ready(function(){
    let countdownTime = 15; // For example, 5 minutes (300 seconds)

    // Function to format time as HH:MM:SS
    function formatTime(seconds) {
        let hrs = Math.floor(seconds / 3600);
        let mins = Math.floor((seconds % 3600) / 60);
        let secs = Math.floor(seconds % 60);
        return `00:${secs.toString().padStart(2, '0')}`;
    }

    // Update the timer every second
    let interval = setInterval(() => {
        countdownTime--; // Decrement countdown time
        if (countdownTime >= 0) {
            let formattedTime = formatTime(countdownTime);
         $('#timer').html(formattedTime); // Update the timer display
        } else {
            clearInterval(interval); // Stop the timer when countdown reaches 0
        $('#timer').html('00:00');
            window.location.href='/CheckLogs'
        }

    }, 1000);


});
</script>



    <div class="row justify-content-center">
        <div class="col-md-12" id="containerD">
            <h2 style="display: flex;justify-content:space-between">
                <div>

                <span style="font-weight: normal;font-size:16px">Employee Name :</span> {{$name}}
                <br>

                </div>
                <div>

                    <button onclick="window.location.href='/CheckLogs'" class="btn btn-danger px-5">Exit</button>


                </div>


            </h2>
            <div style="display:flex;justify-content:flex-end;font-size:40px">
                <span style="font-size:15px;font-weight:bold">Automatically closing in </span>
                <span id="timer" style="color:red;margin-left:4px" >--:--</span>
              </div>
            <br>
            <div class="row">
                <div class="col-md-12">
               <div class="card shadow">
                <div class="card-body p-5">
                    <h4>
                        Daily Time Record
                    </h4>
                    <table class="table">
                        <thead>
                          <tr style="text-align: center" class="table-success">
                            <th scope="col">Time In</th>
                            <th scope="col">Break Out</th>
                            <th scope="col">Break In</th>
                            <th scope="col">Time Out</th>
                          </tr>
                        </thead>
                        <tbody>
                            @if ($dtr)
                              <tr  style="text-align: center;font-size:35px" >
                                <td>{{$dtr->first_in ? date('h:i a',strtotime($dtr->first_in)): "--:--" }}</td>
                                <td>{{$dtr->first_out ? date('h:i a',strtotime($dtr->first_out)): "--:--" }}</td>
                                <td>{{$dtr->second_in ? date('h:i a',strtotime($dtr->second_in)): "--:--" }}</td>
                                <td>{{$dtr->second_out ? date('h:i a',strtotime($dtr->second_out)): "--:--" }}</td>
                            </tr>
                            @else
                            <tr  style="text-align: center" >
                                <td>--:--</td>
                                <td>--:--</td>
                                <td>--:--</td>
                                <td>--:--</td>
                            </tr>
                            @endif

                        </tbody>
                      </table>
                </div>
               </div>
                </div>
                <div class="col-md-12 mt-2">

                    <div class="card shadow">
                        <div class="card-body p-5">
                            <h4>
                                Logs recorded and their sequence.
                            </h4>
               <div class="table-responsive">
                <table class="table table-striped table-striped-columns">
                    <thead>
                      <tr class="table-secondary">
                        <th scope="col">Sequence</th>
                        <th scope="col">Entry</th>
                        <th scope="col">Punch</th>
                        <th scope="col">Device Name</th>
                        <th scope="col">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                        @php
                        $dtlogs = [];
                            if($dtrlogs){
                                $jlogs = json_decode($dtrlogs->json_logs);
                             
                            }
                        @endphp
                        @if (isset($jlogs))
                        @foreach ($jlogs as $item)
                        <tr style="font-size:35px">
                            <td>{{$item->timing + 1}}</td>
                            <td>
                                {{date('h:i a',strtotime($item->date_time))}}
                            </td>
                            <td>
                                @switch($item->status)
                                @case(1)
                                <span class="badge bg-warning">Check-OUT</span>
                                @break
                            @case(0)
                            <span class="badge bg-success">Check-IN</span>
                                @break
                                @case(255)
                                <span class="badge bg-primary">Global State</span>
                                @break
                                @endswitch
                            </td>
                            <td>
                                {{$item->device_name}}
                            </td>
                            <td>
                                @if ($item->entry_status == "Logged")
                                <span style="color:gray"> {{$item->entry_status}}</span>
                                @else
                                <span style="color:rgb(81, 168, 81)"> {{$item->entry_status}}</span>
                                @endif

                            </td>
                        </tr>
                    @endforeach
                        @endif

                    </tbody>
                  </table>
               </div>
                        </div>
                       </div>
                </div>

                <div class="col-md-12 mt-2">

                    <div class="card shadow">
                        <div class="card-body p-5">
                            <h4>
                               <span style="color: rgb(192, 75, 75)">Live</span> Biometric/Device Logs <span style="font-size:13px;">( It is either pulled or not. If you're not seeing data here, it means it has been pulled by the system. )</span>
                            </h4>
                            <table class="table">
                                <thead>
                                  <tr class="table-warning">
                                    <th scope="col">Entry</th>
                                    <th scope="col">Punch</th>
                                    <th scope="col">Assumed Status</th>

                                  </tr>
                                </thead>
                                <tbody>
                                    @foreach ($biologs as $item)
                                        <tr style="font-size:35px">
                                            <td>{{date('h:i a',strtotime($item['date_time']))}}</td>
                                            <td>
                                                @switch($item['status'])
                                                    @case(1)
                                                        <span class="badge bg-warning">Check-OUT</span>
                                                        @break
                                                    @case(0)
                                                    <span class="badge bg-success">Check-IN</span>
                                                        @break
                                                        @case(255)
                                                        <span class="badge bg-primary">Global State</span>
                                                        @break

                                                @endswitch
                                            </td>
                                            <td>
                                                {{$item['entry_status']}}
                                            </td>
                                        </tr>
                                    @endforeach

                                </tbody>
                              </table>
                        </div>
                       </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
