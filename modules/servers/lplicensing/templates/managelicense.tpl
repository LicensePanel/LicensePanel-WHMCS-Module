
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
/* Style the tab */
.tab {
  overflow: hidden;
  border: 1px solid #ccc;
  background-color: #f1f1f1;
}


/* Style the buttons inside the tab */
.tab button {
  background-color: inherit;
  float: left;
  border: none;
  outline: none;
  cursor: pointer;
  padding: 14px 7px;
  transition: 0.3s;
  font-size: 17px;
}

/* Change background color of buttons on hover */
.tab button:hover {
  background-color: #ddd;
}

/* Create an active/current tablink class */
.tab button.active {
  background-color: #ccc;
}

/* Style the tab content */
.tabcontent {
  display: none;
  border-top: none;
}
.floated {

   margin-right:5px;
}
</style>


<h2 style="text-align: left";>License panel</h2>
<p style="text-align: left";>Thank you for trusting us</p>
<div class="tab">
        <button class="tablinks" onclick="openTab(event, 'firstpage')">Main tab</button>
  <button class="tablinks" onclick="openTab(event, 'info')">License info</button>
  <button class="tablinks" onclick="openTab(event, 'changeip')">Change IP</button>
  <button class="tablinks" onclick="openTab(event, 'install')">License installation</button>
</div><div id="info" class="tabcontent">
    <br>
<h4 style="text-align: left";>Your license key</h4>
        <input type="text" class="form-control" readonly="true" value="{$licensekey}" />

        {if $configurableoptions}
            <div class="alert alert-info margin-top-5">
                {foreach from=$configurableoptions item=configoption}
                    <div class="row">
                        <div class="col-xs-5 text-right">
                            <strong>{$configoption.optionname}</strong>
                        </div>
                        <div class="col-xs-7">
                            {if $configoption.optiontype eq 3}
                                {if $configoption.selectedqty}
                                    {$LANG.yes}
                                {else}
                                    {$LANG.no}
                                {/if}
                            {elseif $configoption.optiontype eq 4}
                                {$configoption.selectedqty} x {$configoption.selectedoption}
                            {else}
                                {$configoption.selectedoption}
                            {/if}
                        </div>
                    </div>
                {/foreach}
            </div>
        {/if}
       
        {if !$allowIpConflicts}
<h4 style="text-align: left";>Allowed IP</h4>
            <textarea rows="1" class="form-control" readonly="true">{$validip}</textarea>
        {/if}


        <h4 style="text-align: left";>License status</h4>
        <p style="text-align: left";>
            {$status}
            {if $suspendreason}({$suspendreason}){/if}
        </p><center>
                            
</div></center>

<div id="changeip" class="tabcontent">
    <br>

       {if !$allowreissues}
                <div class="col-xs-4 col-md-12 margin-bottom-5">
                     <h4 style = "text-align: left";>Change license IP address</h4>
                    <form method="post" action="clientarea.php?action=productdetails">
                       
                <input type="text" name="IPc" class="form-control" value="" />
               <br>
                        <input type="hidden" name="id" value="{$id}" />
                        <input type="hidden" name="serveraction" value="custom" />
                        <input type="hidden" name="a" value="ChangeIp" /><center>
                        <button type="submit"  class="btn btn-success"{if $status != "Active"} disabled{/if}>
                            <i class="fa fa-refresh fa-1x"></i>
                            Change IP
                        </button></center>
                    </form>
                    <br><br>
                </div>
                
        {/if}

</div>

<div id="install" class="tabcontent">
    
    <br>
        {if !$allowDirectoryConflicts}
            <h4 style="text-align: left";>Installation command</h4>
            <p style="text-align: left";>You can contact support if you had any issues in installing our licenses</p>
            <textarea rows="5" dir="ltr" class="form-control" readonly="true">{$validdirectory}</textarea>
        {/if}
</div>
<br>

<div id="firstpage" class="tabcontent"></div>
<p style="text-align: left";>You can monitor your license status , view additional info and manage your license using this panel</p>
<p style="text-align: left";>Please do not hesitate to contact us if you had any issue or need additional support. support is free without extra charge</p><br>
<h4 style="text-align: left";>Your license key</h4>
        <input type="text" class="form-control" readonly="true" value="{$licensekey}" />
        
        {if $status == "Active"}
        <br><br>
			<ul>
 <h2 style="text-align: center"; style="padding-left: 20px;">License Status :<strong id="enable" style="padding-left: 20px;">Active</strong>
 
        </ul>        
          {/if}              
                          <style>
    #disable { color: #9d051c; }
    #enable { color: #059d26; }
  </style>


        {if $status != "Active"}
        <br><br>
			<ul>
 <h2 style="text-align: center"; style="padding-left: 20px;">license status :<strong id="disable" style="padding-left: 20px;">Deactive</strong>
 
        </ul>   
        
                        
                        
{/if}
        
        
        <br><br>
{if $status == "Reissued"}
    <div class="alert alert-success text-center">
        {$LANG.licensingaddon.reissuestatusmsg}
    </div>
{/if}


{if $downloads}
    <div class="alert alert-warning text-center licensing-addon-latest-download">
        <h3>{$LANG.licensingaddon.latestdownload}</h3>
        <p>{$downloads.0.description|nl2br}</p>
        <p><a href="{$downloads.0.link}" class="btn btn-default">
            <i class="fa fa-fw fa-download"></i>
            {$LANG.licensingaddon.downloadnow}
        </a></p>
    </div>
{/if}

{foreach $hookOutput as $output}
    <div>
        {$output}
    </div>
{/foreach}




<div class="row">

    <div class="col-md-4 pull-md-right">

        <div class="row">

            

            {if $packagesupgrade}
                <div class="col-xs-4 col-md-12 margin-bottom-10">
                    <a href="upgrade.php?type=package&id={$id}" role="button" class="btn btn-info btn-lg btn-block">
                        <i class="fa fa-arrow-up fa-2x"></i><br />
                        {$LANG.upgrade}
                    </a>
                </div>
            {/if}

            <div class="col-xs-4 col-md-12 margin-bottom-5">
                <form method="post" action="clientarea.php?action=cancel">
                    <input type="hidden" name="id" value="{$id}" />
                    <button type="submit" class="btn btn-danger{if $pendingcancellation} disabled{/if}">
                        <i class=""></i>
                        {if $pendingcancellation}
                            {$LANG.cancellationrequested}
                        {else}
                            {$LANG.cancel}
                        {/if}
                    </button>
                    
                    
                    <style>
     @keyframes bgcolor {
    0% {
        background-color: #b4b4b4
    }

    30% {
        background-color: #818489
    }

    60% {
        background-color: #937596
    }

    90% {
        background-color: #c6b3cf
    }

    100% {
        background-color: #b9d2c0
    }
    
    50% {
        background-color: #828994
    }
}




button1 {
    -webkit-animation: bgcolor 20s infinite;
    animation: bgcolor 10s infinite;
    -webkit-animation-direction: alternate;
    animation-direction: alternate;
}
</style>

                    
                    </form><br>


                    
                
            </div>

        </div>

    </div>
    <div class="col-md-8">
        <style>
.btn-success1 {
    color: #fff;
    background-color: #000000;
    border-color: #000000;
}
</style>

        <style>
.btn-success2 {
    color: #fff;
    background-color: #228223;
    border-color: #228223;
}
</style>


        
        
        
 
    </div>

</div>

<script>
function openTab(evt, TabName) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(TabName).style.display = "block";
  evt.currentTarget.className += " active";
}
</script>

<div class="row">
    <div class="col-sm-4 text-center">
        <h4>{$LANG.clientareahostingregdate}</h4>
        {$regdate}
    </div>
    <div class="col-sm-4 text-center">
        <h4>{$LANG.clientareahostingnextduedate}</h4>
        {$nextduedate}
    </div>
    <div class="col-sm-4 text-center">
        <h4>{$LANG.orderbillingcycle}</h4>
        {$billingcycle}
    </div>
</div>

<div class="row">
    {if $firstpaymentamount neq $recurringamount}
    <div class="col-sm-4 text-center">
        <h4>{$LANG.firstpaymentamount}</h4>
        {$firstpaymentamount}
    </div>
    {/if}
    {if $billingcycle != $LANG.orderpaymenttermonetime && $billingcycle != $LANG.orderpaymenttermfreeaccount}
    <div class="col-sm-4 text-center">
        <h4>{$LANG.recurringamount}</h4>
        {$recurringamount}
    </div>
    {/if}
    {if $firstpaymentamount neq $recurringamount || ($billingcycle != $LANG.orderpaymenttermonetime && $billingcycle != $LANG.orderpaymenttermfreeaccount)}
    <div class="col-sm-4 text-center">
        <h4>{$LANG.orderpaymentmethod}</h4>
        {$paymentmethod}
    </div>
    {/if}
</div>

{if $customfields}
    <div class="row">
        {foreach from=$customfields item=field}
            <div class="col-sm-4 text-center">
                <h4>{$field.name}</h4>
                {if $field.value}{$field.value}{else}-{/if}
            </div>
        {/foreach}
    </div>
{/if}
