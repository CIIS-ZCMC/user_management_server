           
    <td rowspan="{{$rowspan}}">
        <!--FIRST IN -->
        @include('generate_dtr.dtr_separator',['entry'=>'firstin'])
    </td>
    <td rowspan="{{$rowspan}}">
        @include('generate_dtr.dtr_separator',['entry'=>'firstout', 'fspan'=> true ])
    </td>
    <td rowspan="{{$rowspan}}">
        @if (!$halfsched)
        @include('generate_dtr.dtr_separator',['entry'=>'secondin'])
        @endif
    </td>
    <td rowspan="{{$rowspan}}">
        @if (!$halfsched)
        @include('generate_dtr.dtr_separator',['entry'=>'secondout'])
        @endif
    </td>
    <td rowspan="{{$rowspan}}">
        @include('generate_dtr.dtr_separator',['entry'=>'undertime'])
    </td>

 