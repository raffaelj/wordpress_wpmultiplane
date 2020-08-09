
<aside id="privacy-notice">

    <h2>Hinweis zum Datenschutz - Cookies</h2>

    <div class="privacy-info-text">

        <p>Wir binden YouTube-Videos ein, wodurch vom Plattformbetreiber Cookies gesetzt und personenbezogene Daten über sie erhoben werden.<br>
        Um unser Angebot zu verbessern sammeln wir Nutzungsstatistiken mit Matomo. Diese Daten werden nicht an Dritte weitergegeben.</p>

    </div>

    <form id="privacy-notice-form" action="{{ $app['route'] }}" method="post">

        <input id="loadExternalVideos" name="loadExternalVideos" type="checkbox" value="1" checked />
        <label for="loadExternalVideos">
            Nachladen externer Videos von YouTube erlauben
        </label>

        <input id="allowMatomoTracking" name="allowMatomoTracking" type="checkbox" value="1" checked />
        <label for="allowMatomoTracking">
            Sammeln von Nutzungsstatistiken erlauben
        </label>

        <button id="privacy-notice-submit" type="submit">Akzeptieren</button>
        <button id="privacy-notice-cancel" type="reset">Nein</button>

    <p class="text-right"><a href="@base('/datenschutz')">Datenschutzerklärung</a> | <a href="@base('/impressum')">Impressum</a></p>

    </form>

</aside>
