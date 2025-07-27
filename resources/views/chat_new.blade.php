<!DOCTYPE html>
<html>

<head>
    <title>Support Chat Box</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('js/app.js') }}" defer></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        #support-chat-box {
            width: 400px;
            max-height: 80vh;
            position: fixed;
            bottom: 80px;
            right: 20px;
            z-index: 1050;
            display: none;
        }

        #messages {
            height: 300px;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }

        .message-bubble {
            margin: 5px 0;
            padding: 10px;
            border-radius: 10px;
            max-width: 70%;
            word-wrap: break-word;
        }

        .from-me {
            background-color: #d1e7dd;
            margin-left: auto;
            text-align: right;
        }

        .from-them {
            background-color: #e2e3e5;
        }
    </style>
</head>

<body>
    <!-- Chat Toggle Button -->
    <button class="btn btn-primary position-fixed bottom-0 end-0 m-4" id="toggle-chat">Support Chat</button>

    <!-- Support Chat Box -->
    <div class="card shadow" id="support-chat-box">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Support Chat</strong>
            <button type="button" class="btn-close" aria-label="Close" id="close-chat"></button>
        </div>
        <div class="card-body p-0 d-flex">
            <!-- User List -->
            <div class="border-end p-2 overflow-auto" style="width: 35%; max-height: 60vh;" id="user-list">
                <!-- AJAX loads users here -->
            </div>

            <!-- Chat Area -->
            <div class="flex-grow-1 p-2 d-flex flex-column">
                <div id="chat-header" class="fw-bold mb-2 small">Select a user to chat</div>
                <div id="messages" class="mb-2 flex-grow-1 border"></div>
                <form id="message-form" class="input-group">
                    <input type="hidden" id="receiver-id" name="receiver_id">
                    <input type="text" id="message-input" name="message" class="form-control"
                        placeholder="Type message..." required>
                    <button class="btn btn-primary" type="submit">Send</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentUserId = null;

        // Toggle Chat Box
        $('#toggle-chat').on('click', function () {
            $('#support-chat-box').toggle();
            if ($('#support-chat-box').is(':visible')) {
                loadUsers();
            }
        });

        $('#close-chat').on('click', function () {
            $('#support-chat-box').hide();
        });

        // Load User List
        function loadUsers() {
            $.ajax({
                url: '/chat/users',
                method: 'GET',
                success: function (users) {
                    let html = '';
                    users.forEach(user => {
                        html += `<div class="p-2 user-item border-bottom small text-break text-dark fw-medium" style="cursor:pointer" data-id="${user.id}" data-name="${user.name}">
                                    ${user.name}
                                </div>`;
                    });
                    $('#user-list').html(html);
                }
            });
        }

        // Handle User Click
        $(document).on('click', '.user-item', function () {
            currentUserId = $(this).data('id');
            $('#receiver-id').val(currentUserId);
            $('#chat-header').text(`Chat with ${$(this).data('name')}`);
            $('#messages').html('');
            fetchMessages();
        });

        // Fetch Messages
        function fetchMessages() {
            if (!currentUserId) return;
            $.ajax({
                url: `/chat/messages?user_id=${currentUserId}`,
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (messages) {
                    let html = '';
                    messages.forEach(msg => {
                        const isMe = msg.sender_id == {{ Auth::id() }};
                        html += `<div class="message-bubble ${isMe ? 'from-me' : 'from-them'}">
                                    <div><strong>${isMe ? 'You' : msg.sender.name}</strong></div>
                                    ${msg.message}
                                    <div class="small text-muted mt-1">${msg.created_at}</div>
                                </div>`;
                    });
                    $('#messages').html(html).scrollTop($('#messages')[0].scrollHeight);
                }
            });
        }

        // Send Message
        $('#message-form').on('submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: '/chat/send',
                method: 'POST',
                data: $(this).serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function () {
                    $('#message-input').val('');
                    fetchMessages();
                }
            });
        });

        // Realtime Update (Laravel Echo with Reverb)
        window.Echo.private(`chat.{{ Auth::id() }}`)
            .listen('MessageSent', (e) => {
                if (e.message.sender_id == currentUserId || e.message.receiver_id == currentUserId) {
                    fetchMessages();
                }
            });

        // Optional polling fallback
        setInterval(() => {
            if (currentUserId) fetchMessages();
        }, 5000);
    </script>
</body>

</html>