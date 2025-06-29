<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lendu – Login</title>
</head>
<body>
    <h2>Login to Lendu</h2>
    <form id="loginForm">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
    </form>
    <p id="message"></p>

    <script>
        document.getElementById('loginForm').onsubmit = async function(e) {
            e.preventDefault();
            const form = new FormData(this);

            const res = await fetch('../api/auth/login.php', {
                method: 'POST',
                body: form
            });
            const data = await res.json();
            document.getElementById('message').innerText = data.message;

            if (data.status === 'success') {
                setTimeout(() => window.location.href = 'dashboard.php', 1000);
            }
        };
    </script>
</body>
</html>
