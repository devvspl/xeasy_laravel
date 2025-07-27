<button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
   <i class="ri-arrow-up-line"></i>
</button>
{{-- <button id="toggle-chat" class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-4"
   style="z-index: 9999; width: 40px; height: 40px;">
   <i class="ri-chat-1-fill"></i>
</button>
<div id="chat-box" class="card shadow-lg position-fixed bottom-0 end-0 m-4"
   style="width: 350px; max-height: 500px; display: none; z-index: 9999;">
   <div style="background-color: #001868"
      class="card-header text-white d-flex justify-content-between align-items-center p-2">
      <strong>Support Chat</strong>
     <i id="close-chat" class="ri-close-line btn btn-sm" style="cursor: pointer;color:white"></i>

   </div>
   <div class="card-body d-flex flex-column p-2" style="height: 400px;">
      <div class="mb-2">
         <select id="user-select" class="form-select form-select-sm">
            <option value="">-- Select User --</option>
         </select>
      </div>
      <div id="chat-header" class="fw-bold small mb-2">Chat Window</div>
      <div id="messages" class="border rounded p-2 bg-light flex-grow-1 mb-2 overflow-auto" style="font-size: 0.9rem;">
      </div>
      <form id="message-form" class="input-group">
         <input type="hidden" id="receiver-id" name="receiver_id">
         <input type="text" id="message-input" name="message" class="form-control form-control-sm"
            placeholder="Type a message..." required>
         <button class="btn btn-sm btn-primary" type="submit">Send</button>
      </form>
   </div>
</div> --}}
<div id="preloader">
   <div id="status">
      <div class="spinner-border text-primary avatar-sm" role="status">
         <span class="visually-hidden">Loading...</span>
      </div>
   </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ URL::to('/') }}/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="{{ URL::to('/') }}/assets/libs/simplebar/simplebar.min.js"></script>
<script src="{{ URL::to('/') }}/assets/libs/node-waves/waves.min.js"></script>
<script src="{{ URL::to('/') }}/assets/libs/feather-icons/feather.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
<script src="{{ URL::to('/') }}/custom/js/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>
@stack('scripts')
<script src="{{ URL::to('/') }}/assets/js/app.js"></script>
{{-- <script>
   let currentUserId = null;


   $('#toggle-chat').on('click', function () {
      $('#chat-box').toggle();
      $('#messages').html('');
      loadUsers();
   });

   $('#close-chat').on('click', function () {
      $('#chat-box').hide();
   });


   function loadUsers() {
      $.ajax({
         url: '/chat/users',
         method: 'GET',
         success: function (users) {
            $('#user-select').empty().append('<option value="">-- Select User --</option>');
            users.forEach(user => {
               $('#user-select').append(`<option value="${user.id}">${user.name}</option>`);
            });
            $('#user-select').select2({
               dropdownParent: $('#chat-box'),
               width: '100%'
            });
         }
      });
   }


   $('#user-select').on('change', function () {
      currentUserId = $(this).val();
      const name = $(this).find('option:selected').text();
      $('#receiver-id').val(currentUserId);
      $('#chat-header').text(`Chat with ${name}`);
      $('#messages').html('');
      if (currentUserId) fetchMessages();
   });


   function fetchMessages() {
      if (!currentUserId) return;

      $.ajax({
         url: `/chat/messages?user_id=${currentUserId}`,
         method: 'GET',
         headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
         success: function (messages) {
            let html = '';
            messages.forEach(msg => {
               const isMe = msg.sender_id == {{ Auth::id() }};
               const messageStyle = isMe
                  ? 'background-color: #001868; color: #fff; text-align: right; position: relative;'
                  : 'background-color: #fff; color: #000; border: 1px solid #dee2e6; position: relative;';
               const timestampStyle = isMe
                  ? 'color: #ffffffb3; font-size: 12px;'
                  : 'color: #6c757d; font-size: 12px;';

               // Determine read/unread icon
               let readStatus = '';
               if (isMe) {
                  readStatus = msg.is_read == 1
                     ? '<i class="ri-eye-line" style="font-size: 14px; vertical-align: middle;" title="Read"></i>'
                     : '<i class="ri-eye-off-line" style="font-size: 14px; vertical-align: middle;" title="Unread"></i>';
               }

               html += `<div class="mb-1 p-2 rounded" style="${messageStyle}">
                            <div><strong>${isMe ? 'You' : msg.sender.name}</strong></div>
                            ${msg.message}
                            <div style="${timestampStyle}">
                                ${new Date(msg.created_at).toLocaleString()}
                                ${readStatus}
                            </div>`;

               // Delete button for sender's own messages
               if (isMe) {
                  html += `<button onclick="deleteMessage(${msg.id})"
                                 style="position: absolute; top: 5px; left: 5px; background: transparent; border: none; color: #ffcccc; cursor: pointer;"
                                 title="Delete Message">
                                 <i class="ri-delete-bin-line"></i>
                             </button>`;
               }

               html += `</div>`;
            });

            $('#messages').html(html).scrollTop($('#messages')[0].scrollHeight);
         }
      });
   }

   function deleteMessage(messageId) {
      if (!confirm('Are you sure you want to delete this message?')) return;

      $.ajax({
         url: `/chat/message/delete/${messageId}`,
         method: 'POST',
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         success: function () {
            fetchMessages();
         },
         error: function () {
            alert('Failed to delete the message.');
         }
      });
   }


   $('#message-form').on('submit', function (e) {
      e.preventDefault();
      $.ajax({
         url: '/chat/send',
         method: 'POST',
         data: $(this).serialize(),
         headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
         success: function () {
            $('#message-input').val('');
            fetchMessages();
         }
      });
   });


   if (typeof window.Echo !== 'undefined') {
      window.Echo.private(`chat.{{ Auth::id() }}`)
         .listen('MessageSent', (e) => {
            if (e.message.sender_id == currentUserId || e.message.receiver_id == currentUserId) {
               fetchMessages();
            }
         });
   }

   setInterval(fetchMessages, 1000);
</script> --}}
</body>

</html>