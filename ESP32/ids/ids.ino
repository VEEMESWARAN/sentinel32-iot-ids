#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>

const char* ssid = "V M E Z’s iPhone";
const char* password = "Veem1909#";

/*
Example:
http://192.168.1.100/ids/receive_alert.php
*/

const char* serverURL =
"http://172.20.10.2/ids/receive_alert.php";

WebServer server(80);

#define LED_PIN 2

int requestCount = 0;

unsigned long lastCheck = 0;
unsigned long lastAlert = 0;

const int WINDOW = 1000;          // 1 second
const int ALERT_COOLDOWN = 10000; // 10 sec

void handleRoot()
{
    requestCount++;

    server.send(
        200,
        "text/plain",
        "ESP32 IDS Active"
    );
}

String getThreatLevel(int requests)
{
    if(requests > 50)
        return "CRITICAL";

    if(requests > 30)
        return "HIGH";

    if(requests > 15)
        return "MEDIUM";

    return "LOW";
}

void sendAlert(
    String attackType,
    String threatLevel,
    String description
)
{
    if (WiFi.status() == WL_CONNECTED)
    {
        HTTPClient http;

        String ip =
        server.client().remoteIP().toString();

        String postData =
        "ip_address=" + ip +
        "&attack_type=" + attackType +
        "&threat_level=" + threatLevel +
        "&description=" + description;

        Serial.println("Sending To:");
        Serial.println(serverURL);

        Serial.println("POST Data:");
        Serial.println(postData);

        http.begin(serverURL);

        http.addHeader(
            "Content-Type",
            "application/x-www-form-urlencoded"
        );

        int responseCode =
        http.POST(postData);

        Serial.print("HTTP Response: ");
        Serial.println(responseCode);

        if(responseCode > 0)
        {
            String response =
            http.getString();

            Serial.println(response);
        }

        http.end();
    }
}

void setup()
{
    Serial.begin(115200);

    pinMode(LED_PIN, OUTPUT);

    WiFi.begin(
        ssid,
        password
    );

    Serial.println("Connecting...");

    while(
        WiFi.status()
        != WL_CONNECTED
    )
    {
        delay(500);
        Serial.print(".");
    }

    Serial.println();
    Serial.println("Connected");

    Serial.print("ESP32 IP: ");
    Serial.println(
        WiFi.localIP()
    );

    server.on(
        "/",
        handleRoot
    );

    server.begin();

    lastCheck = millis();

    for(int i=0;i<3;i++)
    {
        digitalWrite(
            LED_PIN,
            HIGH
        );

        delay(200);

        digitalWrite(
            LED_PIN,
            LOW
        );

        delay(200);
    }
}

void loop()
{
    server.handleClient();

    if(
        millis()
        - lastCheck
        >= WINDOW
    )
    {
        Serial.print(
            "Requests/sec: "
        );

        Serial.println(
            requestCount
        );

        String threat =
        getThreatLevel(
            requestCount
        );

        if(
            threat != "LOW"
            &&
            millis()
            - lastAlert
            >
            ALERT_COOLDOWN
        )
        {
            digitalWrite(
                LED_PIN,
                HIGH
            );

            String description =
            "Traffic threshold exceeded";

            sendAlert(
                "Traffic Flood",
                threat,
                description
            );

            Serial.println(
                "ALERT SENT"
            );

            delay(1000);

            digitalWrite(
                LED_PIN,
                LOW
            );

            lastAlert =
            millis();
        }

        requestCount = 0;

        lastCheck = millis();
    }
}