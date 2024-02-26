     <td>
        <!--FIRST IN -->
        @include('generate_dtr.DtrSeparator',['entry'=>'firstin','schedule'=>$schedule])
    </td>
    <td>
        @include('generate_dtr.DtrSeparator',['entry'=>'firstout' ,'fspan'=> false])
    </td>
    <td>
        @if (!$halfsched)
        @include('generate_dtr.DtrSeparator',['entry'=>'secondin'])
        @endif

    </td>
    <td>
        @if (!$halfsched)
        @include('generate_dtr.DtrSeparator',['entry'=>'secondout'])
        @endif
    </td>
    <td style="width: 30px !important;">
        @include('generate_dtr.DtrSeparator',['entry'=>'undertime'])
    </td>

