

<div class="wrap">
    <h1>Lipsey's API Importer</h1>
    <form id="lipseys-api-form">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="lipseys_api_email">Email</label></th>
                <td><input name="lipseys_api_email" type="email" id="lipseys_api_email" value="" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="lipseys_api_password">Password</label></th>
                <td><input name="lipseys_api_password" type="password" id="lipseys_api_password" value="" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="lipseys_api_percentage">Profit Margin (%)</label></th>
                <td><input name="lipseys_api_percentage" type="number" id="lipseys_api_percentage" value="0" class="regular-text" required></td>
            </tr>
        </table>
        <p class="submit"><button type="button" id="lipseys-api-fetch" class="button button-primary">Fetch Data</button></p>
    </form>

    <!-- Output area -->
    <div id="output-area">
        <pre class="cmd-output" id="cmd-output"></pre>
    </div>
</div>

<script type="text/javascript">
    document.getElementById('lipseys-api-fetch').addEventListener('click', function () {
        var form = document.getElementById('lipseys-api-form');
        var formData = new FormData(form);

        formData.append('action', 'lipseys_api_fetch'); // Ensure the action is included
        formData.append('lipseys_api_email', document.getElementById('lipseys_api_email').value);
        formData.append('lipseys_api_password', document.getElementById('lipseys_api_password').value);
        formData.append('lipseys_api_percentage', document.getElementById('lipseys_api_percentage').value);

        var outputArea = document.getElementById('cmd-output');
        outputArea.innerHTML = "Starting process...\n";

        fetch(ajaxurl, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.text())
        .then(data => {
            outputArea.innerHTML += data;
        })
        .catch(error => {
            outputArea.innerHTML += "Error: " + error.message + "\n";
        });
    });
</script>
