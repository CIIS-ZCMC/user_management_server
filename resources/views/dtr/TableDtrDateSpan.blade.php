
    <td rowspan="{{$rowspan}}" class="time ">
        <!--FIRST IN -->
        @include('dtr.DtrSeparator',['entry'=>'firstin','schedule'=>$schedule])
    </td>
    <td rowspan="{{$rowspan}}" class="time">
        @include('dtr.DtrSeparator',['entry'=>'firstout', 'fspan'=> true ])
    </td>
    <td rowspan="{{$rowspan}}" class="time">
        @if (!$halfsched)
        @include('dtr.DtrSeparator',['entry'=>'secondin'])
        @endif
    </td>
    <td rowspan="{{$rowspan}}" class="time">
        @if (!$halfsched)
        @include('dtr.DtrSeparator',['entry'=>'secondout'])
        @endif
    </td>
    <td rowspan="{{$rowspan}}" class="time" style="width: 40px !important; padding:10px;border-right :1px solid rgb(196, 197, 201);border-left :1px solid rgb(196, 197, 201)">
        @include('dtr.DtrSeparator',['entry'=>'undertime'])
    </td>

