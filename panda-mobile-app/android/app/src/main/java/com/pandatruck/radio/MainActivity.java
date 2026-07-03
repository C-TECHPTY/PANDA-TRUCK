package com.pandatruck.radio;

import android.Manifest;
import android.app.Activity;
import android.app.DownloadManager;
import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.graphics.Color;
import android.graphics.Typeface;
import android.graphics.drawable.GradientDrawable;
import android.net.Uri;
import android.os.AsyncTask;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.os.Handler;
import android.os.Looper;
import android.view.Gravity;
import android.view.View;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ScrollView;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;

public class MainActivity extends Activity {
    private static final String SITE = "https://pandatruckreloaded.com/";
    private static final String RADIO_URL = SITE + "index.php#radio";
    private static final String NOTICE_CHANNEL = "panda_app_notices";

    private LinearLayout root;
    private LinearLayout content;
    private Handler handler = new Handler(Looper.getMainLooper());
    private SharedPreferences prefs;
    private Runnable noticePoller;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        prefs = getSharedPreferences("panda_app", MODE_PRIVATE);
        requestNotificationPermission();
        createNoticeChannel();
        buildShell();
        showRadio();
        startNoticePolling();
    }

    private void buildShell() {
        root = new LinearLayout(this);
        root.setOrientation(LinearLayout.VERTICAL);
        root.setBackgroundColor(Color.rgb(5, 5, 7));

        LinearLayout header = new LinearLayout(this);
        header.setOrientation(LinearLayout.VERTICAL);
        header.setPadding(dp(20), dp(22), dp(20), dp(14));
        header.setBackground(gradient(Color.rgb(8, 8, 12), Color.rgb(35, 12, 12), 0));

        TextView appTitle = new TextView(this);
        appTitle.setText("PANDA TRUCK");
        appTitle.setTextColor(Color.WHITE);
        appTitle.setTextSize(28);
        appTitle.setGravity(Gravity.CENTER);
        appTitle.setTypeface(Typeface.DEFAULT_BOLD);
        header.addView(appTitle);

        TextView appSub = new TextView(this);
        appSub.setText("RADIO | MIXES | DJS | CHAT");
        appSub.setTextColor(Color.rgb(225, 38, 29));
        appSub.setTextSize(12);
        appSub.setGravity(Gravity.CENTER);
        appSub.setPadding(0, dp(4), 0, 0);
        header.addView(appSub);

        root.addView(header);

        LinearLayout tabs = new LinearLayout(this);
        tabs.setOrientation(LinearLayout.HORIZONTAL);
        tabs.setPadding(dp(10), dp(10), dp(10), dp(10));
        tabs.setBackgroundColor(Color.rgb(12, 12, 14));
        addTab(tabs, "Radio", new View.OnClickListener() { public void onClick(View v) { showRadio(); } });
        addTab(tabs, "Mixes", new View.OnClickListener() { public void onClick(View v) { showMixes(); } });
        addTab(tabs, "DJs", new View.OnClickListener() { public void onClick(View v) { showDjs(); } });
        addTab(tabs, "Chat", new View.OnClickListener() { public void onClick(View v) { showChat(); } });
        addTab(tabs, "Web", new View.OnClickListener() { public void onClick(View v) { openExternal(SITE); } });
        root.addView(tabs);

        content = new LinearLayout(this);
        content.setOrientation(LinearLayout.VERTICAL);
        root.addView(content, new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT, 0, 1
        ));

        setContentView(root);
    }

    private void addTab(LinearLayout parent, String label, View.OnClickListener listener) {
        Button button = new Button(this);
        button.setText(label);
        button.setTextColor(Color.WHITE);
        button.setTextSize(10);
        button.setAllCaps(false);
        button.setBackground(rounded(Color.rgb(28, 28, 32), dp(14)));
        button.setOnClickListener(listener);
        LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(0, dp(42), 1);
        lp.setMargins(dp(2), 0, dp(2), 0);
        parent.addView(button, lp);
    }

    private void clearContent() {
        content.removeAllViews();
    }

    private void showRadio() {
        clearContent();
        ScrollView scroll = new ScrollView(this);
        LinearLayout box = page();
        box.addView(sectionHeader("Radio en vivo", "Servicio nativo en segundo plano"));
        box.addView(radioHero(), fullMargins());

        LinearLayout controls = new LinearLayout(this);
        controls.setGravity(Gravity.CENTER);
        controls.setOrientation(LinearLayout.HORIZONTAL);

        Button play = primaryButton("Play radio");
        play.setOnClickListener(new View.OnClickListener() {
            public void onClick(View v) {
                sendRadioAction(RadioService.ACTION_PLAY);
            }
        });
        Button stop = darkButton("Stop");
        stop.setOnClickListener(new View.OnClickListener() {
            public void onClick(View v) {
                sendRadioAction(RadioService.ACTION_STOP);
            }
        });
        controls.addView(play, new LinearLayout.LayoutParams(0, dp(54), 2));
        controls.addView(stop, new LinearLayout.LayoutParams(0, dp(54), 1));
        box.addView(controls, fullMargins());
        box.addView(small("La radio sigue desde la notificacion del telefono. Para mejor resultado, pon la app sin restricciones de bateria."));

        scroll.addView(box);
        content.addView(scroll, match());
    }

    private View radioHero() {
        LinearLayout hero = new LinearLayout(this);
        hero.setOrientation(LinearLayout.VERTICAL);
        hero.setGravity(Gravity.CENTER);
        hero.setPadding(dp(18), dp(26), dp(18), dp(26));
        hero.setBackground(gradient(Color.rgb(22, 22, 30), Color.rgb(225, 38, 29), dp(24)));

        TextView badge = new TextView(this);
        badge.setText("EN VIVO");
        badge.setTextColor(Color.WHITE);
        badge.setTextSize(12);
        badge.setGravity(Gravity.CENTER);
        badge.setTypeface(Typeface.DEFAULT_BOLD);
        badge.setBackground(rounded(Color.rgb(225, 38, 29), dp(18)));
        hero.addView(badge, new LinearLayout.LayoutParams(dp(92), dp(32)));

        TextView icon = new TextView(this);
        icon.setText("PLAY");
        icon.setTextColor(Color.WHITE);
        icon.setTextSize(24);
        icon.setGravity(Gravity.CENTER);
        icon.setTypeface(Typeface.DEFAULT_BOLD);
        icon.setBackground(rounded(Color.argb(210, 225, 38, 29), dp(90)));
        LinearLayout.LayoutParams ilp = new LinearLayout.LayoutParams(dp(150), dp(150));
        ilp.setMargins(0, dp(18), 0, dp(18));
        hero.addView(icon, ilp);

        TextView name = new TextView(this);
        name.setText("Panda Truck Radio");
        name.setTextColor(Color.WHITE);
        name.setTextSize(22);
        name.setGravity(Gravity.CENTER);
        name.setTypeface(Typeface.DEFAULT_BOLD);
        hero.addView(name);

        TextView sub = new TextView(this);
        sub.setText("Musica en vivo 24/7");
        sub.setTextColor(Color.rgb(235, 235, 235));
        sub.setTextSize(14);
        sub.setGravity(Gravity.CENTER);
        hero.addView(sub);
        return hero;
    }

    private void showMixes() {
        clearContent();
        final ScrollView scroll = new ScrollView(this);
        final LinearLayout box = page();
        box.addView(sectionHeader("Mixes", "Ultimos mixes subidos"));
        box.addView(small("Cargando mixes..."));
        scroll.addView(box);
        content.addView(scroll, match());

        new JsonTask(new JsonTask.Callback() {
            public void done(JSONObject object, JSONArray ignored) {
                box.removeAllViews();
                box.addView(sectionHeader("Mixes", "Ultimos mixes subidos"));
                JSONArray mixes = object != null ? object.optJSONArray("mixes") : null;
                if (mixes == null || mixes.length() == 0) {
                    box.addView(small("No se pudieron cargar mixes."));
                    return;
                }
                int count = Math.min(mixes.length(), 80);
                for (int i = 0; i < count; i++) {
                    JSONObject item = mixes.optJSONObject(i);
                    if (item != null) box.addView(mixCard(item));
                }
            }
        }).execute(SITE + "api/app_mixes.php?limit=40");
    }

    private View mixCard(final JSONObject item) {
        LinearLayout card = card();

        LinearLayout row = new LinearLayout(this);
        row.setOrientation(LinearLayout.HORIZONTAL);
        ImageView cover = coverImage(dp(82), dp(82));
        loadImage(cover, item.optString("cover_url", ""));
        row.addView(cover);

        LinearLayout info = new LinearLayout(this);
        info.setOrientation(LinearLayout.VERTICAL);
        info.setPadding(dp(12), 0, 0, 0);
        info.addView(cardTitle(item.optString("title", "Mix")));
        info.addView(small(item.optString("dj", "Panda Truck")));
        info.addView(tiny(item.optInt("downloads") + " descargas"));
        row.addView(info, new LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1));
        card.addView(row);

        LinearLayout actions = new LinearLayout(this);
        actions.setOrientation(LinearLayout.HORIZONTAL);
        Button listen = darkButton("Escuchar");
        listen.setOnClickListener(new View.OnClickListener() {
            public void onClick(View v) {
                playMix(item.optString("audio_url", ""), item.optString("audio_fallback_url", ""), item.optString("title", "Mix"));
            }
        });
        Button download = primaryButton("Descargar");
        download.setOnClickListener(new View.OnClickListener() {
            public void onClick(View v) {
                downloadMix(
                        item.optString("download_url", ""),
                        item.optString("direct_download_url", item.optString("audio_fallback_url", "")),
                        item.optString("title", "mix")
                );
            }
        });
        actions.addView(listen, new LinearLayout.LayoutParams(0, dp(46), 1));
        actions.addView(download, new LinearLayout.LayoutParams(0, dp(46), 1));
        card.addView(actions);
        return card;
    }

    private void showDjs() {
        clearContent();
        final ScrollView scroll = new ScrollView(this);
        final LinearLayout box = page();
        box.addView(sectionHeader("DJs", "Artistas destacados"));
        box.addView(small("Cargando DJs..."));
        scroll.addView(box);
        content.addView(scroll, match());

        new JsonTask(new JsonTask.Callback() {
            public void done(JSONObject object, JSONArray ignored) {
                box.removeAllViews();
                box.addView(sectionHeader("DJs", "Artistas destacados"));
                JSONArray djs = object != null ? object.optJSONArray("djs") : null;
                if (djs == null || djs.length() == 0) {
                    box.addView(small("No se pudieron cargar DJs."));
                    return;
                }
                for (int i = 0; i < djs.length(); i++) {
                    JSONObject dj = djs.optJSONObject(i);
                    if (dj != null) box.addView(djCard(dj));
                }
            }
        }).execute(SITE + "api/app_djs.php?limit=40");
    }

    private View djCard(final JSONObject dj) {
        LinearLayout card = card();
        LinearLayout row = new LinearLayout(this);
        row.setOrientation(LinearLayout.HORIZONTAL);

        ImageView avatar = coverImage(dp(76), dp(76));
        loadImage(avatar, dj.optString("avatar_url", ""));
        row.addView(avatar);

        LinearLayout info = new LinearLayout(this);
        info.setOrientation(LinearLayout.VERTICAL);
        info.setPadding(dp(12), 0, 0, 0);
        info.addView(cardTitle(dj.optString("name", "DJ")));
        info.addView(small(dj.optString("genre", "Musica") + " | " + dj.optString("city", "")));
        info.addView(tiny(dj.optInt("total_mixes") + " mixes | " + dj.optInt("total_downloads") + " descargas"));
        row.addView(info, new LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1));
        card.addView(row);

        final int id = dj.optInt("id");
        Button open = primaryButton("Ver DJ");
        open.setOnClickListener(new View.OnClickListener() {
            public void onClick(View v) {
                openUrl(SITE + "dj.php?slug=" + id);
            }
        });
        card.addView(open, new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, dp(48)));
        return card;
    }

    private void showWeb(String url) {
        clearContent();
        WebView web = new WebView(this);
        WebSettings settings = web.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setMediaPlaybackRequiresUserGesture(false);
        settings.setUserAgentString(settings.getUserAgentString() + " PandaTruckAndroidApp/3.0");
        web.setWebChromeClient(new WebChromeClient());
        web.setWebViewClient(new WebViewClient() {
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                Uri uri = request.getUrl();
                if (uri != null && "pandatruckreloaded.com".equalsIgnoreCase(uri.getHost())) return false;
                startActivity(new Intent(Intent.ACTION_VIEW, uri));
                return true;
            }
        });
        content.addView(web, match());
        web.loadUrl(url);
    }

    private void showChat() {
        clearContent();
        ScrollView scroll = new ScrollView(this);
        LinearLayout box = page();
        box.addView(sectionHeader("Chat en vivo", "Saludos y comunidad Panda Truck"));

        TextView chatCard = new TextView(this);
        chatCard.setText("Entra al chat para mandar saludos, compartir con otros oyentes y seguir escuchando la radio desde la notificacion.");
        chatCard.setTextColor(Color.WHITE);
        chatCard.setTextSize(16);
        chatCard.setPadding(dp(18), dp(22), dp(18), dp(22));
        chatCard.setBackground(gradient(Color.rgb(25, 25, 32), Color.rgb(225, 38, 29), dp(20)));
        box.addView(chatCard, fullMargins());

        Button openChat = primaryButton("Abrir chat");
        openChat.setOnClickListener(new View.OnClickListener() {
            public void onClick(View v) {
                showWeb(SITE + "sala-chat.php");
            }
        });
        box.addView(openChat, new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, dp(54)));

        TextView note = small("El chat usa la misma sala del sitio para mantener mensajes, moderacion y usuarios sincronizados.");
        box.addView(note);
        scroll.addView(box);
        content.addView(scroll, match());
    }

    private LinearLayout page() {
        LinearLayout box = new LinearLayout(this);
        box.setOrientation(LinearLayout.VERTICAL);
        box.setPadding(dp(18), dp(18), dp(18), dp(28));
        return box;
    }

    private LinearLayout card() {
        LinearLayout card = new LinearLayout(this);
        card.setOrientation(LinearLayout.VERTICAL);
        card.setPadding(dp(14), dp(14), dp(14), dp(14));
        card.setBackground(rounded(Color.rgb(22, 22, 26), dp(18)));
        card.setLayoutParams(fullMargins());
        return card;
    }

    private View sectionHeader(String heading, String subheading) {
        LinearLayout box = new LinearLayout(this);
        box.setOrientation(LinearLayout.VERTICAL);
        box.addView(title(heading));
        box.addView(tiny(subheading));
        return box;
    }

    private TextView title(String text) {
        TextView view = new TextView(this);
        view.setText(text);
        view.setTextColor(Color.WHITE);
        view.setTextSize(27);
        view.setTypeface(Typeface.DEFAULT_BOLD);
        view.setPadding(0, 0, 0, dp(8));
        return view;
    }

    private TextView cardTitle(String text) {
        TextView view = new TextView(this);
        view.setText(text);
        view.setTextColor(Color.WHITE);
        view.setTextSize(17);
        view.setTypeface(Typeface.DEFAULT_BOLD);
        return view;
    }

    private TextView small(String text) {
        TextView view = new TextView(this);
        view.setText(text == null ? "" : text);
        view.setTextColor(Color.rgb(185, 185, 190));
        view.setTextSize(13);
        view.setPadding(0, dp(4), 0, dp(8));
        return view;
    }

    private TextView tiny(String text) {
        TextView view = new TextView(this);
        view.setText(text == null ? "" : text);
        view.setTextColor(Color.rgb(145, 145, 150));
        view.setTextSize(12);
        view.setPadding(0, dp(3), 0, dp(6));
        return view;
    }

    private Button primaryButton(String text) {
        Button b = new Button(this);
        b.setText(text);
        b.setTextColor(Color.WHITE);
        b.setAllCaps(false);
        b.setBackground(rounded(Color.rgb(225, 38, 29), dp(14)));
        return b;
    }

    private Button darkButton(String text) {
        Button b = new Button(this);
        b.setText(text);
        b.setTextColor(Color.WHITE);
        b.setAllCaps(false);
        b.setBackground(rounded(Color.rgb(42, 42, 48), dp(14)));
        return b;
    }

    private ImageView coverImage(int width, int height) {
        ImageView image = new ImageView(this);
        image.setBackground(rounded(Color.rgb(36, 36, 42), dp(16)));
        image.setScaleType(ImageView.ScaleType.CENTER_CROP);
        image.setImageResource(com.pandatruck.radio.R.drawable.ic_stat_radio);
        image.setPadding(dp(12), dp(12), dp(12), dp(12));
        image.setLayoutParams(new LinearLayout.LayoutParams(width, height));
        return image;
    }

    private GradientDrawable rounded(int color, int radius) {
        GradientDrawable drawable = new GradientDrawable();
        drawable.setColor(color);
        drawable.setCornerRadius(radius);
        return drawable;
    }

    private GradientDrawable gradient(int start, int end, int radius) {
        GradientDrawable drawable = new GradientDrawable(
                GradientDrawable.Orientation.TL_BR,
                new int[]{start, end}
        );
        drawable.setCornerRadius(radius);
        return drawable;
    }

    private LinearLayout.LayoutParams fullMargins() {
        LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
        );
        lp.setMargins(0, dp(8), 0, dp(10));
        return lp;
    }

    private LinearLayout.LayoutParams match() {
        return new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.MATCH_PARENT
        );
    }

    private int dp(int value) {
        return (int) (value * getResources().getDisplayMetrics().density + 0.5f);
    }

    private void sendRadioAction(String action) {
        Intent intent = new Intent(this, RadioService.class);
        intent.setAction(action);
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) startForegroundService(intent);
        else startService(intent);
    }

    private void playMix(String url, String fallbackUrl, String title) {
        if (url == null || url.length() == 0) {
            url = fallbackUrl;
        }
        if (url == null || url.length() == 0) {
            Toast.makeText(this, "Audio no disponible", Toast.LENGTH_SHORT).show();
            return;
        }
        Intent intent = new Intent(this, RadioService.class);
        intent.setAction(RadioService.ACTION_PLAY_URL);
        intent.putExtra(RadioService.EXTRA_URL, url);
        intent.putExtra(RadioService.EXTRA_FALLBACK_URL, fallbackUrl);
        intent.putExtra(RadioService.EXTRA_TITLE, title);
        intent.putExtra(RadioService.EXTRA_LIVE, false);
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) startForegroundService(intent);
        else startService(intent);
        Toast.makeText(this, "Reproduciendo mix", Toast.LENGTH_SHORT).show();
    }

    private void downloadMix(String url, String fallbackUrl, String title) {
        try {
            if (url == null || url.length() == 0) {
                url = fallbackUrl;
            }
            if (url == null || url.length() == 0) {
                Toast.makeText(this, "Descarga no disponible", Toast.LENGTH_SHORT).show();
            } else {
                openExternal(url);
                Toast.makeText(this, "Abriendo descarga", Toast.LENGTH_SHORT).show();
            }
        } catch (Exception e) {
            if (fallbackUrl != null && fallbackUrl.length() > 0) openExternal(fallbackUrl);
        }
    }

    private String safeFilename(String value) {
        String clean = value == null ? "mix" : value.replaceAll("[^a-zA-Z0-9_-]+", "_");
        clean = clean.replaceAll("_+", "_");
        if (clean.length() == 0) clean = "mix";
        if (clean.length() > 80) clean = clean.substring(0, 80);
        return clean;
    }

    private void openUrl(String url) {
        showWeb(url);
    }

    private void openExternal(String url) {
        startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(url)));
    }

    private void loadImage(ImageView view, String url) {
        if (url == null || url.length() == 0) return;
        new ImageTask(view).execute(url);
    }

    private void startNoticePolling() {
        noticePoller = new Runnable() {
            public void run() {
                pollNotices();
                handler.postDelayed(this, 45000);
            }
        };
        handler.postDelayed(noticePoller, 3000);
    }

    private void pollNotices() {
        int lastId = prefs.getInt("last_notice_id", 0);
        new JsonTask(new JsonTask.Callback() {
            public void done(JSONObject object, JSONArray ignored) {
                JSONArray notifications = object != null ? object.optJSONArray("notifications") : null;
                if (notifications == null) return;
                for (int i = 0; i < notifications.length(); i++) {
                    JSONObject notice = notifications.optJSONObject(i);
                    if (notice == null) continue;
                    int id = notice.optInt("id");
                    if (id > prefs.getInt("last_notice_id", 0)) {
                        prefs.edit().putInt("last_notice_id", id).apply();
                        showNativeNotice(notice);
                    }
                }
            }
        }).execute(SITE + "api/app_notifications.php?last_id=" + lastId);
    }

    private void showNativeNotice(JSONObject notice) {
        Intent intent = new Intent(this, MainActivity.class);
        PendingIntent pi = PendingIntent.getActivity(this, 90, intent, pendingFlags());
        Notification.Builder builder = Build.VERSION.SDK_INT >= Build.VERSION_CODES.O
                ? new Notification.Builder(this, NOTICE_CHANNEL)
                : new Notification.Builder(this);
        builder.setSmallIcon(com.pandatruck.radio.R.drawable.ic_stat_radio)
                .setContentTitle(notice.optString("title", "Panda Truck"))
                .setContentText(notice.optString("body", "Nuevo aviso"))
                .setContentIntent(pi)
                .setAutoCancel(true)
                .setDefaults(Notification.DEFAULT_ALL);
        NotificationManager manager = (NotificationManager) getSystemService(NOTIFICATION_SERVICE);
        if (manager != null) manager.notify(800 + notice.optInt("id"), builder.build());
    }

    private int pendingFlags() {
        int flags = PendingIntent.FLAG_UPDATE_CURRENT;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) flags |= PendingIntent.FLAG_IMMUTABLE;
        return flags;
    }

    private void createNoticeChannel() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return;
        NotificationChannel channel = new NotificationChannel(
                NOTICE_CHANNEL,
                "Avisos Panda Truck",
                NotificationManager.IMPORTANCE_DEFAULT
        );
        NotificationManager manager = getSystemService(NotificationManager.class);
        if (manager != null) manager.createNotificationChannel(channel);
    }

    private void requestNotificationPermission() {
        if (Build.VERSION.SDK_INT >= 33 &&
                checkSelfPermission(Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
            requestPermissions(new String[]{Manifest.permission.POST_NOTIFICATIONS}, 100);
        }
        if (Build.VERSION.SDK_INT <= 28 &&
                checkSelfPermission(Manifest.permission.WRITE_EXTERNAL_STORAGE) != PackageManager.PERMISSION_GRANTED) {
            requestPermissions(new String[]{Manifest.permission.WRITE_EXTERNAL_STORAGE}, 101);
        }
    }

    @Override
    protected void onDestroy() {
        if (noticePoller != null) handler.removeCallbacks(noticePoller);
        super.onDestroy();
    }

    static class JsonTask extends AsyncTask<String, Void, String> {
        interface Callback {
            void done(JSONObject object, JSONArray array);
        }

        private Callback callback;

        JsonTask(Callback callback) {
            this.callback = callback;
        }

        protected String doInBackground(String... urls) {
            HttpURLConnection conn = null;
            try {
                conn = (HttpURLConnection) new URL(urls[0]).openConnection();
                conn.setConnectTimeout(10000);
                conn.setReadTimeout(15000);
                BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                StringBuilder out = new StringBuilder();
                String line;
                while ((line = reader.readLine()) != null) out.append(line);
                reader.close();
                return out.toString();
            } catch (Exception e) {
                return "";
            } finally {
                if (conn != null) conn.disconnect();
            }
        }

        protected void onPostExecute(String result) {
            try {
                if (result != null && result.trim().startsWith("[")) {
                    callback.done(null, new JSONArray(result));
                } else {
                    callback.done(new JSONObject(result == null ? "{}" : result), null);
                }
            } catch (Exception e) {
                callback.done(null, null);
            }
        }
    }

    static class ImageTask extends AsyncTask<String, Void, Bitmap> {
        private ImageView target;

        ImageTask(ImageView target) {
            this.target = target;
        }

        protected Bitmap doInBackground(String... urls) {
            HttpURLConnection conn = null;
            try {
                conn = (HttpURLConnection) new URL(urls[0]).openConnection();
                conn.setConnectTimeout(10000);
                conn.setReadTimeout(15000);
                conn.connect();
                return BitmapFactory.decodeStream(conn.getInputStream());
            } catch (Exception e) {
                return null;
            } finally {
                if (conn != null) conn.disconnect();
            }
        }

        protected void onPostExecute(Bitmap bitmap) {
            if (bitmap != null && target != null) {
                target.setPadding(0, 0, 0, 0);
                target.setImageBitmap(bitmap);
            }
        }
    }
}
