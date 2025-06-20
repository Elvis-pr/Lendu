<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lendu – Register</title>
</head>
<body>
    <h2>Register for Lendu</h2>
    <form id="registerForm">
        <input type="text" name="full_name" placeholder="Full Name" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="text" name="student_id" placeholder="Student ID" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Register</button>
    </form>
    <p id="message"></p>

    <script>
        document.getElementById('registerForm').onsubmit = async function(e) {
            e.preventDefault();
            const form = new FormData(this);

            const res = await fetch('../api/auth/register.php', {
                method: 'POST',
                body: form
            });
            const data = await res.json();
            document.getElementById('message').innerText = data.message;
        };
    </script>
</body>
</html>
