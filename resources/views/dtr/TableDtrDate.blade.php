     <td class="time">
        <!--FIRST IN -->
        @include('dtr.DtrSeparator',['entry'=>'firstin','schedule'=>$schedule])
    </td>
    <td class="time">
        @include('dtr.DtrSeparator',['entry'=>'firstout' ,'fspan'=> false])
    </td>
    <td class="time">
        @if (!$halfsched)
        @include('dtr.DtrSeparator',['entry'=>'secondin'])
        @endif

    </td>
    <td class="time">
        @if (!$halfsched)
        @include('dtr.DtrSeparator',['entry'=>'secondout'])
        @endif
    </td>
    <td style="width: 40px !important; padding:10px;border-right :1px solid rgb(196, 197, 201);border-left :1px solid rgb(196, 197, 201)" class="time">
        @include('dtr.DtrSeparator',['entry'=>'undertime'])
    </td>

