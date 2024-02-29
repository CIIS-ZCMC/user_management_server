<style>
   @import url('https://fonts.googleapis.com/css2?family=Onest:wght@200&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&display=swap')
    body {
        display: flex;
        justify-content: center;
        font-family: "Roboto Condensed", sans-serif;
        user-select: none;
    }

    #po {
        width: 100%;
        padding: 5px;
    }

    #tabledate {
        width: 100%;
        background-color: #F8F4EA;
        padding: 10px;
        border-collapse: collapse;
        text-align: center;
    }

    #tabledate tr th {
        font-size: 13px;
        text-transform: uppercase;
        color:#597E52;
        border: 1px solid rgb(177, 181, 185);
    }

    #tabledate tr td {
        padding: 5px;
        border-top: 1px solid rgb(196, 197, 201);
    }

    #tabledate tr .time {
        font-weight: bold;
        color:#57805e;

    }
    #tabledate tr .timefirstarrival {
        font-weight: normal;
        text-transform: uppercase;
        font-size: 12px;
    }
    #tabledate #tblheader tr td {
        font-weight: normal;
        font-size: 13px;
        color:#637A9F;
    }

</style>

<div id="po" >

    <table id="tabledate" >
        <tr>
            <th colspan="2" style="background-color: whitesmoke" >
                Day
            </th>

            <th>Arrival</th>
            <th>Departure</th>
            <th>Arrival</th>
            <th>Departure</th>
            <th>
                UNDERTIME
                <table id="tblheader">
                    <tr>
                        <td>Hours</td>
                        <td>Minutes</td>
                    </tr>
                </table>
            </th>
            <th style="background-color: whitesmoke">
                Remarks
            </th>
        </tr>

        {{-- {{print_r($dtrRecords)}} --}}
        <tbody>
            @php
                $isExcept = false;
            @endphp
            @for($i = 1; $i <= $daysInMonth; $i++)

            @php
            $checkIn = array_filter($dtrRecords, function ($res) use ($i) {
                return date('d', strtotime($res['first_in'])) == $i
                    && date('d', strtotime($res['first_out'])) == $i + 1;
            });

            $val = 0;
            $outdd = array_map(function($res) {
                return [
                    'first_out' => $res['first_out']
                ];
            }, $checkIn);
            @endphp

            <tr>
                <td style="color:#3468C0;text-align:center;width:60px;border-right :1px solid rgb(196, 197, 201);background-color: whitesmoke">   {{date('d', strtotime(date('Y-m-d', strtotime($year.'-'.$month.'-'.$i))))}}

                </td>
                <td style="width: 80px;border-right :1px solid rgb(196, 197, 201);background-color: whitesmoke">
                    <span style="color:#637A9F; font-size:13px">
                        {{date('l', strtotime(date('Y-m-d', strtotime($year.'-'.$month.'-'.$i))))}}
                    </span>
                </td>

                @include('dtr.TableDtrDate',['schedule'=>$schedule])
                {{-- @php $rowspan = count($outdd) > 0 ? 2 : 1; @endphp

                @if ($rowspan > 1)
                    @php
                        $isExcept = true;
                    @endphp

                 @include('dtr.TableDtrDateSpan',['schedule'=>$schedule])
                @else
                    @if ($isExcept == true)

                        @php
                            $isExcept = false;
                        @endphp
                    @else
                      @include('dtr.TableDtrDate',['schedule'=>$schedule])
                    @endif
                @endif --}}

                @if (count($checkIn) >= 1)
                    @php $val = $i; @endphp
                @endif

            </tr>
        @endfor
        </tbody>
    </table>




</div>

<script>
    document.addEventListener("keydown", function (event) {
  if (event.keyCode === 123) {
    event.preventDefault();
  }
});

document.addEventListener("contextmenu", function (e) {
  e.preventDefault();
});


document.addEventListener("keydown", function (e) {
  if (e.key === "F12" || (e.ctrlKey && e.shiftKey && (e.key === "I" || e.key === "J"))) {
    e.preventDefault();
  }
});

</script>
