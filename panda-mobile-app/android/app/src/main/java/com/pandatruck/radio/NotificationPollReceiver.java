package com.pandatruck.radio;

import android.Manifest;
import android.app.AlarmManager;
import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.os.Build;
import android.os.SystemClock;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;

public class NotificationPollReceiver extends BroadcastReceiver {
    private static final String ENDPOINT = "https://pandatruckreloaded.com/api/app_notifications.php?last_id=";
    private static final String CHANNEL = "panda_app_notices";
    private static final long INTERVAL = 15 * 60 * 1000L;

    public static void schedule(Context context) {
        AlarmManager manager = (AlarmManager) context.getSystemService(Context.ALARM_SERVICE);
        if (manager == null) return;
        Intent intent = new Intent(context, NotificationPollReceiver.class);
        PendingIntent pending = PendingIntent.getBroadcast(context, 5071, intent, pendingFlags());
        manager.setInexactRepeating(
                AlarmManager.ELAPSED_REALTIME_WAKEUP,
                SystemClock.elapsedRealtime() + INTERVAL,
                INTERVAL,
                pending
        );
    }

    @Override
    public void onReceive(final Context context, Intent intent) {
        final PendingResult result = goAsync();
        new Thread(new Runnable() {
            public void run() {
                try { check(context.getApplicationContext()); }
                finally { result.finish(); }
            }
        }).start();
    }

    private void check(Context context) {
        SharedPreferences prefs = context.getSharedPreferences("panda_app", Context.MODE_PRIVATE);
        int lastId = prefs.getInt("last_notice_id", 0);
        HttpURLConnection conn = null;
        try {
            conn = (HttpURLConnection) new URL(ENDPOINT + lastId).openConnection();
            conn.setConnectTimeout(10000);
            conn.setReadTimeout(12000);
            BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
            StringBuilder body = new StringBuilder();
            String line;
            while ((line = reader.readLine()) != null) body.append(line);
            reader.close();
            JSONArray notices = new JSONObject(body.toString()).optJSONArray("notifications");
            if (notices == null) return;

            int newestId = lastId;
            if (lastId == 0) {
                for (int i = 0; i < notices.length(); i++) {
                    JSONObject notice = notices.optJSONObject(i);
                    if (notice != null) newestId = Math.max(newestId, notice.optInt("id"));
                }
                if (newestId > 0) prefs.edit().putInt("last_notice_id", newestId).apply();
                return;
            }

            for (int i = 0; i < notices.length(); i++) {
                JSONObject notice = notices.optJSONObject(i);
                if (notice == null) continue;
                int id = notice.optInt("id");
                if (id > lastId) show(context, notice);
                newestId = Math.max(newestId, id);
            }
            if (newestId > lastId) prefs.edit().putInt("last_notice_id", newestId).apply();
        } catch (Exception ignored) {
        } finally {
            if (conn != null) conn.disconnect();
        }
    }

    private void show(Context context, JSONObject notice) {
        if (Build.VERSION.SDK_INT >= 33 &&
                context.checkSelfPermission(Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) return;

        NotificationManager manager = (NotificationManager) context.getSystemService(Context.NOTIFICATION_SERVICE);
        if (manager == null) return;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel channel = new NotificationChannel(CHANNEL, "Avisos Panda Truck", NotificationManager.IMPORTANCE_DEFAULT);
            manager.createNotificationChannel(channel);
        }

        String title = notice.optString("title", "Panda Truck Reloaded");
        Intent open = new Intent(context, MainActivity.class);
        open.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);
        if (title.toLowerCase().contains("mix")) open.putExtra("open_section", "mixes");
        PendingIntent pending = PendingIntent.getActivity(context, notice.optInt("id"), open, pendingFlags());
        Notification.Builder builder = Build.VERSION.SDK_INT >= Build.VERSION_CODES.O
                ? new Notification.Builder(context, CHANNEL)
                : new Notification.Builder(context);
        builder.setSmallIcon(com.pandatruck.radio.R.drawable.ic_stat_radio)
                .setContentTitle(title)
                .setContentText(notice.optString("body", "Nuevo aviso"))
                .setStyle(new Notification.BigTextStyle().bigText(notice.optString("body", "Nuevo aviso")))
                .setContentIntent(pending)
                .setAutoCancel(true)
                .setDefaults(Notification.DEFAULT_ALL);
        manager.notify(9000 + notice.optInt("id"), builder.build());
    }

    private static int pendingFlags() {
        int flags = PendingIntent.FLAG_UPDATE_CURRENT;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) flags |= PendingIntent.FLAG_IMMUTABLE;
        return flags;
    }
}
