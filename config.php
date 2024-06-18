<?php
if (!defined('WPINC')) {
    die();
}
//Variables used in the forms
$token = get_option('version_reporter_token');
//Websites monitored
$websites = get_option('version_reporter_websites');
//Get the latest version of wordpress
$updates = get_core_updates();
?>
<div class="wrap">
    <h1>Version Reporter Settings</h1>
    <h2>Current available version online is <span id="available-version"><?= $updates[0]->version; ?></span></h2>
    <form method="post">
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="venue_id">Security Token</label>
                    </th>
                    <td>
                        <input id="version-checker-token" type="text" name="version_reporter_token" value=""
                            class="regular-text" />
                        <button onClick="(function(length) {
                            let result = '';
                            const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*(){}[]';
                            const charactersLength = characters.length;
                            let counter = 0;
                            while (counter < length) {
                                result += characters.charAt(Math.floor(Math.random() * charactersLength));
                                counter += 1;
                            }
                            let field = document.getElementById('version-checker-token');
                            field.value = result;
                        })(30)" type="button" class="button button-primary">Generate new</button>
                        <?php if (isset($token) && $token): ?>
                            <p>
                                <?php
                                $length = mb_strlen($token);
                                $length = floor($length / 3);
                                $token = str_pad(mb_substr($token, 0, $length), mb_strlen($token), '*');
                                ?>
                                Curren token: <?= $token; ?>
                            </p>
                        <?php endif; ?>
                        <p>
                            Min token length should be at least 10 characters! If less it wouldn't be saved
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="venue_id">Add websites</label>
                    </th>
                    <td>
                        <textarea rows="10" cols="70" name="version_reporter_website"
                            placeholder="Place each url on a new line"></textarea>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="row">
                    </th>
                    <td>
                        <button class="button button-primary button-large">Save</button>
                    </td>
                </tr>
            </tfoot>
        </table>
    </form>
</div>
<div class="wrap">
    <h1>Websites</h1>
    <table id="websites" class="wp-list-table widefat fixed striped table-view-list pages" role="presentation"
        data-url="version-reporter.json">
        <thead>
            <tr>
                <th>Website</th>
                <th>Version</th>
                <th></th>
            </tr>
        </thead>
        <?php if (isset($websites) && $websites): ?>
            <tbody>
                <?php foreach ($websites as $i => $row): ?>
                    <tr class="website" data-id="<?= $i; ?>">
                        <td class="url">
                            <a href="<?= $row['url']; ?>" target="_blank"><?= $row['url']; ?></a>
                        </td>
                        <td class="version">
                            <?= (isset($row['version']) ? $row['version'] : null); ?>
                        </td>
                        <td style="text-align: right">
                            <button class="remove button button-primary">&times;</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        <?php endif; ?>
    </table>
</div>
<br>
<div>
    <button id="check-version" data-url="<?= home_url('/version-check'); ?>"
        class="button button-primary button-large">Check Versions</button>
</div>
<script>
    "use strict";
    (function () {
        document.getElementById('check-version').addEventListener('click', function (e) {
            let websites = document.getElementById('websites').querySelectorAll('.website');
            for (let i = 0; i < websites.length; i++) {
                let website = websites[i];
                checkVersion(website);
            }
        });

        function checkVersion(el) {
            //trigger
            let available = document.getElementById('available-version');
            let button = document.getElementById('check-version');
            el.querySelector('.version').classList.remove("latest");
            el.querySelector('.version').classList.remove("older");
            el.querySelector('.version').innerHTML = '...';
            let record = el.closest(".website");
            let url = record.querySelector('.url a').innerHTML;
            let xhr = new XMLHttpRequest();
            let formData = new FormData();
            formData.append("url", url.trim());

            xhr.open("POST", button.dataset.url);
            xhr.send(formData);
            xhr.onloadend = function (data) {
                if (xhr.status == 200) {
                    let response = JSON.parse(xhr.response);
                    if (response.success) {
                        if (available.innerHTML.trim() == response.version.trim()) {
                            el.querySelector('.version').classList.add("latest");
                        } else if (available.innerHTML.trim() != response.version.trim()) {
                            el.querySelector('.version').classList.add("older");
                        }
                        el.querySelector('.version').innerHTML = response.version;
                    } else {
                        el.querySelector('.version').innerHTML = response.error;
                    }
                }
            }
        }

        let remove_buttons = document.querySelectorAll('.remove');
        if (remove_buttons.length > 0) {
            for (let i = 0; i < remove_buttons.length; i++) {
                remove_buttons[i].addEventListener('click', function (e) {
                    e.preventDefault();
                    let record = this.closest(".website");
                    let url = record.querySelector('.url a').innerHTML;
                    let xhr = new XMLHttpRequest();
                    let formData = new FormData();
                    formData.append("version_reporter_remove", url.trim());
                    xhr.open("POST", window.location);
                    xhr.send(formData);
                    xhr.onloadend = function () {
                        if (xhr.status == 200) {
                            record.remove();
                        }
                    }
                });
            }
        }
    })();
</script>
<style>
    .version.latest {
        color: green;
    }

    .version.older {
        color: red;
    }
</style>