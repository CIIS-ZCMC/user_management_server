
    <td rowspan="{{$rowspan}}">
        <!--FIRST IN -->
        @include('generate_dtr.DtrSeparator',['entry'=>'firstin','schedule'=>$schedule])
    </td>
    <td rowspan="{{$rowspan}}">
        @include('generate_dtr.DtrSeparator',['entry'=>'firstout', 'fspan'=> true ])
    </td>
    <td rowspan="{{$rowspan}}">
        @if (!$halfsched)
        @include('generate_dtr.DtrSeparator',['entry'=>'secondin'])
        @endif
    </td>
    <td rowspan="{{$rowspan}}">
        @if (!$halfsched)
        @include('generate_dtr.DtrSeparator',['entry'=>'secondout'])
        @endif
    </td>
    <td rowspan="{{$rowspan}}">
        @include('generate_dtr.DtrSeparator',['entry'=>'undertime'])
    </td>

