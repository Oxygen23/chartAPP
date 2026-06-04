function showError(id, message) {
    var element = document.getElementById(id);

    if (element) {
        element.textContent = message;
    }
}

function clearErrors() {
    var errors = document.querySelectorAll(".field-error");

    errors.forEach(function (error) {
        error.textContent = "";
    });
}

function validIdentifier(identifier) {
    if (identifier.trim().length < 3) {
        return false;
    }

    if (identifier.includes("@")) {
        return identifier.includes(".");
    }

    return /^[a-zA-Z0-9._-]+$/.test(identifier);
}

var sessionTimeout = document.body.getAttribute("data-session-timeout");

if (sessionTimeout) {
    var timeoutTime = Number(sessionTimeout) * 1000;
    var sessionTimer;

    function logoutAfterInactivity() {
        window.location.href = "login.php?timeout=1";
    }

    function resetSessionTimer() {
        clearTimeout(sessionTimer);
        sessionTimer = setTimeout(logoutAfterInactivity, timeoutTime);
    }

    ["click", "keydown", "mousemove", "scroll", "touchstart"].forEach(function (eventName) {
        document.addEventListener(eventName, resetSessionTimer);
    });

    resetSessionTimer();
}

var signupForm = document.getElementById("signupForm");

if (signupForm) {
    signupForm.addEventListener("submit", function (event) {
        clearErrors();

        var username = document.getElementById("signupUsername").value.trim();
        var email = document.getElementById("signupEmail").value.trim();
        var password = document.getElementById("signupPassword").value;
        var confirmPassword = document.getElementById("confirmPassword").value;
        var hasError = false;

        if (username.length < 3) {
            showError("signupUsernameError", "Username must be at least 3 characters long.");
            hasError = true;
        }

        if (!validEmail(email)) {
            showError("signupEmailError", "Enter a valid email address.");
            hasError = true;
        }

        if (password.length < 6) {
            showError("signupPasswordError", "Password must be at least 6 characters long.");
            hasError = true;
        }

        if (password !== confirmPassword) {
            showError("confirmPasswordError", "Passwords do not match.");
            hasError = true;
        }

        if (hasError) {
            event.preventDefault();
        }
    });
}

var loginForm = document.getElementById("loginForm");

if (loginForm) {
    loginForm.addEventListener("submit", function (event) {
        clearErrors();

        var email = document.getElementById("loginEmail").value.trim();
        var password = document.getElementById("loginPassword").value;
        var hasError = false;

        if (!validIdentifier(email)) {
            showError("loginEmailError", "Enter your email or username.");
            hasError = true;
        }

        if (password.length < 1) {
            showError("loginPasswordError", "Enter your password.");
            hasError = true;
        }

        if (hasError) {
            event.preventDefault();
        }
    });
}

var roomForm = document.getElementById("roomForm");

if (roomForm) {
    roomForm.addEventListener("submit", function (event) {
        clearErrors();

        var roomName = document.getElementById("roomName").value.trim();
        var roomPattern = /^[a-zA-Z0-9_-]+$/;

        if (roomName.length < 3 || !roomPattern.test(roomName)) {
            showError("roomNameError", "Use letters, numbers, _ or -, and enter at least 3 characters.");
            event.preventDefault();
        }
    });
}

var messageForm = document.getElementById("messageForm");
var messageInput = document.getElementById("messageInput");

if (messageForm) {
    messageForm.addEventListener("submit", function (event) {
        clearErrors();

        var message = document.getElementById("messageInput").value.trim();

        if (message.length < 1) {
            showError("messageError", "Write a message first.");
            event.preventDefault();
        }
    });
}

if (messageInput && messageForm) {
    messageInput.addEventListener("keydown", function (event) {
        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault();
            messageForm.requestSubmit();
        }
    });
}

var deleteForms = document.querySelectorAll(".delete-message-form");

deleteForms.forEach(function (form) {
    form.addEventListener("submit", function (event) {
        var confirmDelete = confirm("Are you sure you want to delete this message?");

        if (!confirmDelete) {
            event.preventDefault();
        }
    });
});

var notifyBox = document.querySelector(".notify-box");

function playNotificationSound() {
    var AudioContext = window.AudioContext || window.webkitAudioContext;

    if (!AudioContext) {
        return;
    }

    var audioContext = new AudioContext();
    var oscillator = audioContext.createOscillator();
    var gain = audioContext.createGain();

    oscillator.type = "sine";
    oscillator.frequency.setValueAtTime(880, audioContext.currentTime);
    oscillator.frequency.setValueAtTime(660, audioContext.currentTime + 0.12);

    gain.gain.setValueAtTime(0.0001, audioContext.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.25, audioContext.currentTime + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.35);

    oscillator.connect(gain);
    gain.connect(audioContext.destination);

    oscillator.start();
    oscillator.stop(audioContext.currentTime + 0.35);
}

if (notifyBox) {
    var notifyCount = notifyBox.getAttribute("data-notify-count");
    document.title = "(" + notifyCount + ") " + document.title;
    var soundPlayed = false;

    if ("Notification" in window && Notification.permission === "default") {
        Notification.requestPermission();
    }

    if ("Notification" in window && Notification.permission === "granted") {
        new Notification("Chat App", {
            body: "You have " + notifyCount + " new message(s). Open the room to read them."
        });
    }

    try {
        playNotificationSound();
        soundPlayed = true;
    } catch (error) {
        soundPlayed = false;
    }

    document.addEventListener("click", function () {
        if (!soundPlayed) {
            playNotificationSound();
            soundPlayed = true;
        }
    }, { once: true });
}

var messagesBox = document.getElementById("messagesBox");

if (messagesBox) {
    messagesBox.scrollTop = messagesBox.scrollHeight;
}
