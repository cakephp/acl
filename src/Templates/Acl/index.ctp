<div class="alert-return"></div>

<ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#users"><?= __('Users') ?></a></li>
    <li><a data-toggle="tab" href="#groups"><?= __('Groups') ?></a></li>
</ul>

<div class="tab-content">
    <div id="users" class="tab-pane fade in active">
        <h3><?= __('Users') ?></h3>

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <p><?php $params=["options" => $users, "label"=> false, "class" => "form-control col-sm-12 col-md-4", "div" => false ];
                    echo $this->Form->input ( 'user_id', $params ); ?></p>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <table class="table table-striped ajaxAclUsers" id="usersTable">
                    <thead>
                        <tr>
                            <th>Menu</th>
                            <th>Ação</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="groups" class="tab-pane fade">
        <h3><?= __('Groups') ?></h3>

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <p><?php $params=["label"=> false, "class" => "form-control col-sm-12 col-md-4", "div" => false ];
                    echo $this->Form->input ( 'group_id', $params ); ?></p>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <table class="table table-striped ajaxAclGroups" id="groupsTable">
                    <thead>
                    <tr>
                        <th>Menu</th>
                        <th>Ação</th>
                        <th>Status</th>
                    </tr>
                    </thead>

                    <tbody>
                </table>
            </div>
        </div>
    </div>
</div>