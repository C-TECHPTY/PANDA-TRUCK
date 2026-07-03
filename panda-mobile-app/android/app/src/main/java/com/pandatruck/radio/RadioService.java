package com.pandatruck.radio;

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.Service;
import android.content.Intent;
import android.media.AudioAttributes;
import android.media.MediaPlayer;
import android.media.session.MediaSession;
import android.media.session.PlaybackState;
import android.net.Uri;
import android.os.Build;
import android.os.Handler;
import android.os.IBinder;
import android.os.Looper;
import android.os.PowerManager;

import java.util.HashMap;
import java.util.Map;

public class RadioService extends Service {
    public static final String ACTION_PLAY = "com.pandatruck.radio.PLAY";
    public static final String ACTION_PLAY_URL = "com.pandatruck.radio.PLAY_URL";
    public static final String ACTION_STOP = "com.pandatruck.radio.STOP";
    public static final String EXTRA_URL = "url";
    public static final String EXTRA_FALLBACK_URL = "fallback_url";
    public static final String EXTRA_TITLE = "title";
    public static final String EXTRA_LIVE = "live";

    private static final String CHANNEL_ID = "panda_radio_playback";
    private static final int NOTIFICATION_ID = 507;
    private static final String STREAM_URL = "https://stream.zeno.fm/vjsa6jiwafavv";

    private final Handler handler = new Handler(Looper.getMainLooper());
    private MediaPlayer player;
    private MediaSession mediaSession;
    private boolean shouldPlay;
    private boolean liveStream = true;
    private String currentUrl = STREAM_URL;
    private String fallbackUrl = "";
    private String currentTitle = "Panda Truck Radio";

    @Override
    public void onCreate() {
        super.onCreate();
        createChannel();
        mediaSession = new MediaSession(this, "PandaTruckPlayer");
        mediaSession.setCallback(new MediaSession.Callback() {
            @Override
            public void onPlay() {
                playCurrent();
            }

            @Override
            public void onPause() {
                stopPlayback();
            }

            @Override
            public void onStop() {
                stopPlayback();
            }
        });
        mediaSession.setActive(true);
    }

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        String action = intent != null ? intent.getAction() : ACTION_PLAY;
        if (ACTION_STOP.equals(action)) {
            stopPlayback();
        } else if (ACTION_PLAY_URL.equals(action)) {
            currentUrl = intent.getStringExtra(EXTRA_URL);
            fallbackUrl = intent.getStringExtra(EXTRA_FALLBACK_URL);
            currentTitle = intent.getStringExtra(EXTRA_TITLE);
            liveStream = intent.getBooleanExtra(EXTRA_LIVE, false);
            if (currentTitle == null || currentTitle.length() == 0) currentTitle = liveStream ? "Panda Truck Radio" : "Mix";
            if (currentUrl == null || currentUrl.length() == 0) currentUrl = STREAM_URL;
            if (fallbackUrl == null) fallbackUrl = "";
            playCurrent();
        } else {
            currentUrl = STREAM_URL;
            fallbackUrl = "";
            currentTitle = "Panda Truck Radio";
            liveStream = true;
            playCurrent();
        }
        return START_STICKY;
    }

    private void playCurrent() {
        shouldPlay = true;
        handler.removeCallbacksAndMessages(null);
        startForeground(NOTIFICATION_ID, buildNotification("Conectando...", true));
        updateState(PlaybackState.STATE_BUFFERING);

        releasePlayer();
        player = new MediaPlayer();
        player.setWakeMode(getApplicationContext(), PowerManager.PARTIAL_WAKE_LOCK);
        player.setAudioAttributes(new AudioAttributes.Builder()
                .setUsage(AudioAttributes.USAGE_MEDIA)
                .setContentType(AudioAttributes.CONTENT_TYPE_MUSIC)
                .build());
        player.setOnPreparedListener(new MediaPlayer.OnPreparedListener() {
            @Override
            public void onPrepared(MediaPlayer mp) {
                if (!shouldPlay) return;
                mp.start();
                startForeground(NOTIFICATION_ID, buildNotification(liveStream ? "Reproduciendo en vivo" : "Reproduciendo mix", true));
                updateState(PlaybackState.STATE_PLAYING);
            }
        });
        player.setOnErrorListener(new MediaPlayer.OnErrorListener() {
            @Override
            public boolean onError(MediaPlayer mp, int what, int extra) {
                if (liveStream) reconnect();
                else if (!tryFallback()) stopPlayback();
                return true;
            }
        });
        player.setOnCompletionListener(new MediaPlayer.OnCompletionListener() {
            @Override
            public void onCompletion(MediaPlayer mp) {
                if (liveStream) reconnect();
                else stopPlayback();
            }
        });

        try {
            Map<String, String> headers = new HashMap<String, String>();
            headers.put("User-Agent", "PandaTruckAndroidApp/3.1");
            headers.put("Accept", "audio/*,*/*");
            player.setDataSource(getApplicationContext(), Uri.parse(currentUrl), headers);
            player.prepareAsync();
        } catch (Exception e) {
            if (liveStream) reconnect();
            else if (!tryFallback()) stopPlayback();
        }
    }

    private boolean tryFallback() {
        if (fallbackUrl == null || fallbackUrl.length() == 0 || fallbackUrl.equals(currentUrl)) {
            return false;
        }
        currentUrl = fallbackUrl;
        fallbackUrl = "";
        playCurrent();
        return true;
    }

    private void reconnect() {
        if (!shouldPlay) return;
        startForeground(NOTIFICATION_ID, buildNotification("Reconectando...", true));
        updateState(PlaybackState.STATE_BUFFERING);
        releasePlayer();
        handler.postDelayed(new Runnable() {
            @Override
            public void run() {
                if (shouldPlay) playCurrent();
            }
        }, 5000);
    }

    private void stopPlayback() {
        shouldPlay = false;
        handler.removeCallbacksAndMessages(null);
        releasePlayer();
        updateState(PlaybackState.STATE_STOPPED);
        stopForeground(true);
        stopSelf();
    }

    private void releasePlayer() {
        if (player == null) return;
        try {
            player.stop();
        } catch (Exception ignored) {
        }
        try {
            player.release();
        } catch (Exception ignored) {
        }
        player = null;
    }

    private Notification buildNotification(String text, boolean playing) {
        Intent openIntent = new Intent(this, MainActivity.class);
        PendingIntent openPending = PendingIntent.getActivity(this, 1, openIntent, pendingFlags());

        Intent controlIntent = new Intent(this, RadioService.class);
        controlIntent.setAction(playing ? ACTION_STOP : ACTION_PLAY);
        PendingIntent controlPending = PendingIntent.getService(this, 2, controlIntent, pendingFlags());

        Notification.Builder builder = Build.VERSION.SDK_INT >= Build.VERSION_CODES.O
                ? new Notification.Builder(this, CHANNEL_ID)
                : new Notification.Builder(this);

        builder.setContentTitle(currentTitle)
                .setContentText(text)
                .setSmallIcon(com.pandatruck.radio.R.drawable.ic_stat_radio)
                .setContentIntent(openPending)
                .setOngoing(playing)
                .setShowWhen(false)
                .setVisibility(Notification.VISIBILITY_PUBLIC)
                .addAction(
                        playing ? android.R.drawable.ic_media_pause : android.R.drawable.ic_media_play,
                        playing ? "Stop" : "Play",
                        controlPending
                );

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            builder.setStyle(new Notification.MediaStyle()
                    .setMediaSession(mediaSession.getSessionToken())
                    .setShowActionsInCompactView(0));
        }

        return builder.build();
    }

    private int pendingFlags() {
        int flags = PendingIntent.FLAG_UPDATE_CURRENT;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            flags |= PendingIntent.FLAG_IMMUTABLE;
        }
        return flags;
    }

    private void updateState(int state) {
        if (mediaSession == null) return;
        long actions = PlaybackState.ACTION_PLAY | PlaybackState.ACTION_PAUSE | PlaybackState.ACTION_STOP;
        mediaSession.setPlaybackState(new PlaybackState.Builder()
                .setActions(actions)
                .setState(state, PlaybackState.PLAYBACK_POSITION_UNKNOWN, 1f)
                .build());
    }

    private void createChannel() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return;
        NotificationChannel channel = new NotificationChannel(
                CHANNEL_ID,
                "Panda Truck Audio",
                NotificationManager.IMPORTANCE_LOW
        );
        channel.setDescription("Reproduccion de radio y mixes");
        NotificationManager manager = getSystemService(NotificationManager.class);
        if (manager != null) manager.createNotificationChannel(channel);
    }

    @Override
    public void onDestroy() {
        shouldPlay = false;
        handler.removeCallbacksAndMessages(null);
        releasePlayer();
        if (mediaSession != null) {
            mediaSession.release();
            mediaSession = null;
        }
        super.onDestroy();
    }

    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }
}
