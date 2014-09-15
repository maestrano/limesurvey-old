<?php echo CHtml::form(array("admin/facci/save"), 'post', array('class'=>'form30', 'id'=>'facciform')); ?>
  <div class='header ui-widget-header'><?php $clang->eT("Meeting with customers and prospects"); ?></div>
  <div id="section1" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
    <ul>

      <li>
        <label for='meeting_date'><?php $clang->eT("Date of the Meeting"); ?></label>
        <input class='popupdate' type='text' size='10' id='meeting_date' name='meeting_date' required="required" />
        <font color='red' face='verdana' size='1'> <?php $clang->eT("Required"); ?></font>
      </li>

      <li>
        <label for='customer_type'><?php $clang->eT("Did you meet a prospect or an existing FACCI customer?"); ?></label>
        <table>
          <tr><td><input type="radio" name="customer_type" value="prospect">Prospect - Not a FACCI customer</input></td></tr>
          <tr><td><input type="radio" name="customer_type" value="existing">Partner of FACCI - Not a customer</input></td></tr>
          <tr><td><input type="radio" name="customer_type" value="existing">Existing FACCI customer</input></td></tr>
        </table>
      </li>

      <li>
        <label for='organziation'><?php $clang->eT("Name of the organisation you met"); ?></label>
        <select name='organziation' id='organziation'>
          <option value="">Select Organization</option>
          <?php
            foreach ($organizations as $organization) {
              echo '<option value="' . $organization->mno_uid . '">' . $organization->title . '</option>';
            }
          ?>
        </select>
      </li>
      <li>
        <label for='new_organziation'><?php $clang->eT("Or create a new Organisation"); ?></label>
        <input type='text' id='new_organziation' name='new_organziation' />
        <br/><br/>
      </li>

      <li>
        <label for='person'><?php $clang->eT("Who did you meet in this organisation?"); ?></label>
        <select name='person' id='person'>
          <option value="">Select Person</option>
          <?php
            foreach ($persons as $person) {
              echo '<option value="' . $person->mno_uid . '">' . $person->title . '</option>';
            }
          ?>
        </select>
      </li>
      <li>
        <label for='new_person_title'><?php $clang->eT("Or create a new Contact"); ?></label>
        <input type='text' size="10" id='new_person_title' name='new_person_title' placeholder="title" />
        <input type='text' id='new_person_first_name' name='new_person_first_name' placeholder="First name" />
        <input type='text' id='new_person_last_name' name='new_person_last_name' placeholder="Last name" />
      </li>

    </ul>
  </div>

  <div class='header ui-widget-header'><?php $clang->eT("Discussion subject(s) / Topic(s)"); ?></div>
  <div id="section2" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
    <ul>
      <li>
        <label for='topic1'><?php $clang->eT("Subject 1"); ?></label>
        <input type='text' size="120" id='topic1' name='topic1' />
      </li>
      <li>
        <label for='topic2'><?php $clang->eT("Subject 2"); ?></label>
        <input type='text' size="120" id='topic1' name='topic2' />
      </li>
      <li>
        <label for='topic3'><?php $clang->eT("Subject 3"); ?></label>
        <input type='text' size="120" id='topic3' name='topic3' />
      </li>
    </ul>
  </div>

  <div class='header ui-widget-header'>
    <?php $clang->eT("Next steps and actions"); ?>
    <button type="button" id="add-action-link" class="limebutton ui-state-default ui-corner-all">Add Action</button>
  </div>

  <div id="actions-sections" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
    <div style="padding: 5px; margin-left: 17px; clear: both; font-weight: bold; float: left;">
      <div style="width: 203px; float: left;">Action type</div>
      <div style="width: 313px; float: left;">Action description</div>
      <div style="width: 203px; float: left;">Action assigned to</div>
      <div style="width: 100px; float: left;">Due date</div>
    </div>
  </div>

  <p>
    <input type='submit' value='<?php $clang->eT("Save meeting summary"); ?>' />
  </p>

</form>

<script type="text/javascript">
  $(document).ready(function() {
      var max_fields      = 10;
      var wrapper         = $("#actions-sections");
      var add_button      = $("#add-action-link");
    
      var x = 1;
      $(add_button).click(function(e) {
          e.preventDefault();
          if(x < max_fields) {
              x++;
              $(wrapper).append('<div style="margin: 5px; padding: 5px; margin-left: 10px; clear: both; border: 1px solid #B0B0B0;"> \
                  <div> \
                    <select name="actions[]" style="width: 200px;"> \
                      <option value="Follow up discussion">Follow up discussion</option> \
                      <option value="Send documents">Send documents</option> \
                      <option value="Send proposal">Send proposal</option> \
                      <option value="Invite to event">Invite to event</option> \
                    </select> \
                    <input type="text" name="action_descriptions[]" placeholder="Action description" style="width: 300px;"/> \
                    <select name="action_assignees[]" style="width: 200px;"> \
                      <?php
                        foreach ($users as $user) {
                          echo "<option value=" . $user->mno_uid . ">" . $user->title . "</option>";
                        }
                      ?>
                    </select> \
                    <input class="popupdate" type="text" size="10" name="action_due_dates[]" placeholder="Due date" /> \
                    <button type="button" class="remove_field limebutton ui-state-default ui-corner-all">Remove Action</button> \
                  </div> \
                  <div> \
                    <input type="text" name="action_others[]" placeholder="Other action type" style="width: 190px;"/> \
                  </div> \
                </div>');
          }

          $(".popupdate:last").each(function(i,e) {
            format=$('#dateformat'+e.name).val();
            if(!format) format = userdateformat;
            $(e).datepicker({ dateFormat: format,
                showOn: 'button',
                changeYear: true,
                changeMonth: true,
                duration: 'fast'
            }, $.datepicker.regional[userlanguage]);
        });
      });
     
      // $(wrapper).on("click",".remove_field", function(e){ //user click on remove text
      //     e.preventDefault(); $(this).parent('div').remove(); x--;
      // })

      $(add_button).trigger('click');
  });
</script>