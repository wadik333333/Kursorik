<?php
require_once 'api/config.php';
require_once 'api/auth_check.php';
$myPhone = json_encode($_SESSION['phone']);
$myName = json_encode($_SESSION['name']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>DolbnyaPro</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <link rel="stylesheet" href="emoji-picker.css" />
  <link rel="stylesheet" href="chat-list.css" />
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="mediamanager.css" />
  <link rel="stylesheet" href="web-layout.css" />
  
</head>
<body>

<audio id="messageSound" src="sounds/1.mp3" preload="auto"></audio>

<div id="recordingWaves">
  <div class="wave"></div><div class="wave"></div><div class="wave"></div><div class="wave"></div><div class="wave"></div>
</div>

<div class="container">
  <div class="screen" id="chatListScreen">
    <div class="header">
      <div>DolbnyaPro</div>
      <div>
        <i class="fas fa-search" onclick="showSearch()" style="margin-right:16px;cursor:pointer;"></i>
        <i class="fas fa-ellipsis-vertical" onclick="toggleMenu()" style="cursor:pointer;"></i>
      </div>
    </div>
    <div id="menuPopup">
      <div onclick="openOwnProfile()">Настройки</div>
      <div onclick="uploadAvatar()">Добавить аватар</div>
      <div onclick="toggleMobileMode()">Мобильная версия</div>
      <div onclick="logout()">Выйти</div>
    </div>
    <div class="chat-list" id="chatList"></div>
    <div id="createChatButton" style="position:fixed;bottom:20px;right:20px;width:56px;height:56px;background:#128c7e;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,0.3);">+</div>
  </div>

  <div class="screen" id="searchScreen">
    <div class="header">
      <i class="fas fa-arrow-left" onclick="backToChats()" style="margin-right:16px;cursor:pointer;"></i>
      <input type="text" id="searchInput" placeholder="Поиск по имени или номеру..." />
    </div>
    <div class="chat-list" id="searchResults"></div>
  </div>

  <div class="screen" id="chatScreen">
    <div class="header" style="position:sticky;top:0;z-index:10;background:#128c7e;color:white;padding:12px 16px;display:flex;align-items:center;justify-content:flex-start;">
      <i class="fas fa-arrow-left" onclick="backToChats()" style="margin-right:12px;cursor:pointer;font-size:18px;"></i>
      <div class="avatar-container" style="position:relative;display:inline-block;">
        <div id="chatAvatar" class="avatar" style="cursor:pointer;width:40px;height:40px;display:flex;align-items:center;justify-content:center;">В</div>
        <span id="chatStatusDot" class="status-dot" style="position:absolute;bottom:0;right:0;width:10px;height:10px;border-radius:50%;border:1px solid white;box-sizing:content-box;display:none;"></span>
      </div>
      <div id="chatName" style="cursor:pointer;margin-left:12px;">Загрузка...</div>
    </div>
    <div class="messages" id="messagesBox"></div>
    
    <!-- 🔹 Блок цитаты над полем ввода -->
    <div id="replyPreview">
      <span id="replyPreviewText"></span>
      <i class="fas fa-times reply-cancel" onclick="cancelReply()"></i>
    </div>
    
    <div class="input-area">
      <button type="button" id="emojiButton" class="emoji-button"><i class="far fa-smile"></i></button>
      <button type="button" class="attach-button"><i class="fas fa-paperclip"></i></button>
      <input type="text" id="messageInput" placeholder="" autocomplete="off" />
      <button type="button" id="voiceRecordButton" class="voice-button"><i class="fas fa-microphone"></i></button>
      <button type="button" class="send-button"><i class="far fa-paper-plane"></i></button>
    </div>
    <div id="emojiPicker" class="emoji-picker"></div>
  </div>
</div>

<div id="createChatModal">
  <div>
    <h3 style="margin-top:0;">Создать чат</h3>
    <input type="text" id="groupName" placeholder="Название чата" style="width:100%;padding:10px;margin:10px 0;border:1px solid #ddd;border-radius:5px;">
    <h4>Добавить участников:</h4>
    <input type="text" id="groupSearchInput" placeholder="Поиск по имени или номеру..." style="width:100%;padding:10px;margin:10px 0;border:1px solid #ddd;border-radius:5px;">
    <div id="groupSearchResults"></div>
    <div id="selectedParticipants"></div>
    <button onclick="createGroupChat()" style="background:#128c7e;color:white;border:none;padding:10px 15px;border-radius:5px;width:100%;">Создать</button>
    <button onclick="closeCreateChatModal()" style="background:#ccc;margin-top:10px;border:none;padding:10px 15px;border-radius:5px;width:100%;">Отмена</button>
  </div>
</div>

<script>
var myPhone = <?= $myPhone ?>;
var myName = <?= $myName ?>;
var currentChatPhone = '', currentChatName = '', currentGroupId = null, isGroupChat = false;
var autoUpdateInterval = null, lastUnreadCount = {}, lastMessageTimestamp = 0, isFirstLoad = true;
var isChatListActive = false, isChatScreenActive = false;

// 🔹 Глобальные переменные для ответа
window.replyToMessageId = null;
window.replyToMessageText = '';

if (!window.selectedParticipants) window.selectedParticipants = new Set();

function minPhone(a, b) { return a && b ? (a < b ? a : b) : ''; }
function maxPhone(a, b) { return a && b ? (a > b ? a : b) : ''; }

function getChatId(phone1, phone2, isGroup = false, groupId = null) {
  if (isGroup && groupId) return groupId;
  return phone1 && phone2 ? minPhone(phone1, phone2) + '_' + maxPhone(phone1, phone2) : '';
}

// 🔹 Новые функции для управления цитатой
function showReplyPreview(text) {
  const preview = document.getElementById('replyPreview');
  const previewText = document.getElementById('replyPreviewText');
  if (preview && previewText) {
    previewText.textContent = text;
    preview.style.display = 'block';
  }
}

function cancelReply() {
  window.replyToMessageId = null;
  window.replyToMessageText = '';
  const preview = document.getElementById('replyPreview');
  if (preview) preview.style.display = 'none';
  const input = document.getElementById('messageInput');
  if (input) input.placeholder = '';
}

// 🔹 Прокрутка к цитируемому сообщению
function scrollToMessage(messageId) {
  const el = document.querySelector(`.message[data-message-id="${messageId}"]`);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.style.backgroundColor = '#fffac8';
    setTimeout(() => el.style.backgroundColor = '', 2000);
  }
}

function showScreen(id) {
  document.querySelectorAll('.screen').forEach(el => el.style.display = 'none');
  const screen = document.getElementById(id);
  if (!screen) return;
  screen.style.display = 'flex';

  stopGlobalChatUpdates(); stopAutoUpdate();
  if (typeof stopOnlinePing === 'function') stopOnlinePing();

  isChatListActive = isChatScreenActive = false;

  if (id === 'chatListScreen') {
    isChatListActive = true;
    if (myPhone && typeof updateChatListData === 'function') {
      updateChatListData();
      startGlobalChatUpdates();
    }
  } else if (id === 'chatScreen') {
    isChatScreenActive = true;
    startAutoUpdate();
  }

  if (myPhone) {
    const isActive = ['chatListScreen', 'chatScreen', 'contactInfoScreen', 'searchScreen'].includes(id);
    if (isActive && typeof startOnlinePing === 'function') startOnlinePing(myPhone);
    else if (!isActive && typeof sendOfflineStatus === 'function') sendOfflineStatus(myPhone);
  }
}

function startAutoUpdate() {
  if (autoUpdateInterval) clearInterval(autoUpdateInterval);
  autoUpdateInterval = setInterval(() => {
    if (isChatScreenActive) {
      loadChatMessages();
      if (!isGroupChat && currentChatPhone) updateChatHeaderStatusFromList(currentChatPhone);
    }
  }, 2000);
}

function stopAutoUpdate() {
  if (autoUpdateInterval) clearInterval(autoUpdateInterval), autoUpdateInterval = null;
}

document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('createChatButton');
  if (btn) btn.onclick = () => {
    document.getElementById('createChatModal').style.display = 'block';
    window.selectedParticipants.clear();
    if (typeof window.updateSelectedParticipants === 'function') window.updateSelectedParticipants();
  };
});

function closeCreateChatModal() {
  const modal = document.getElementById('createChatModal');
  modal.style.display = 'none';
  ['groupName', 'groupSearchInput'].forEach(id => document.getElementById(id).value = '');
  ['groupSearchResults', 'selectedParticipants'].forEach(id => document.getElementById(id).innerHTML = '');
  window.selectedParticipants.clear();
}

async function createGroupChat() {
  const name = document.getElementById('groupName').value.trim();
  if (!name) return alert('Введите название чата');
  const participants = Array.from(window.selectedParticipants || []);
  if (participants.length < 1) return alert('Выберите хотя бы одного участника');
  try {
    const res = await fetch('api/create_group.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, creator: myPhone, creatorName: myName, participants })
    });
    const data = await res.json();
    if (data.success) {
      closeCreateChatModal();
      if (typeof updateChatListData === 'function') updateChatListData();
    } else alert('Ошибка: ' + (data.error || 'Не удалось создать чат'));
  } catch (e) {
    console.error('Ошибка создания группы:', e);
    alert('Ошибка соединения с сервером');
  }
}

async function markChatAsRead(chatId, messages) {
  if (!chatId || !messages?.length) return;
  let maxTs = 0;
  for (const msg of messages) if (msg.sender !== myPhone && msg.timestamp > maxTs) maxTs = msg.timestamp;
  if (maxTs <= 0) return;
  try {
    const res = await fetch('api/mark_read.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ chatId, timestamp: maxTs })
    });
    const result = await res.json();
    if (result.success) {
      delete lastUnreadCount[chatId];
      setTimeout(() => { if (typeof updateChatListData === 'function') updateChatListData(); }, 100);
    }
  } catch (e) { console.error('Ошибка при отметке прочитанным:', e); }
}

async function updateChatHeaderStatusFromList(phone) {
  const dot = document.getElementById('chatStatusDot');
  if (!dot || !phone || isGroupChat) return dot && (dot.style.display = 'none');
  try {
    const res = await fetch(`api/get_chats_list.php?phone=${encodeURIComponent(myPhone)}&t=${Date.now()}`);
    const chats = await res.json();
    const chat = Array.isArray(chats) ? chats.find(c => c.phone === phone) : null;
    dot.style.background = chat?.online ? '#4CAF50' : '#9E9E9E';
    dot.style.display = 'inline-block';
  } catch (e) {
    console.error('Ошибка статуса:', e);
    dot.style.background = '#9E9E9E';
    dot.style.display = 'inline-block';
  }
}

function updateStatusDot(avatarContainer, isGroup, online) {
  if (isGroup || !avatarContainer) return;
  let dot = avatarContainer.querySelector('.status-dot');
  if (online === undefined) return dot?.remove();
  if (!dot) {
    dot = document.createElement('span');
    dot.className = 'status-dot';
    avatarContainer.appendChild(dot);
  }
  dot.style.background = online ? '#4CAF50' : '#9E9E9E';
  dot.style.display = 'inline-block';
}

// 🔹 ОБНОВЛЁННАЯ ФУНКЦИЯ: теперь поддерживает цитату
function addOptimisticMessage(text, mediaUrl = null, isVoice = false, duration = 0, tempMessageId, replyText = null, replySenderName = null) {
  const box = document.getElementById('messagesBox');
  if (!box) return;
  const now = new Date();
  const timeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
  const div = document.createElement('div');
  div.className = 'message sent optimistic';
  div.dataset.timestamp = Math.floor(now.getTime() / 1000);
  div.dataset.messageId = tempMessageId;
  let content = '';

  // 🔹 Отображаем цитату сразу!
  if (replyText !== null || replySenderName !== null) {
    const rName = replySenderName || '...';
    const rText = replyText || '[Медиа]';
    content += `
      <div class="message-reply" style="padding:4px 8px;background:#f0f0f0;border-radius:6px;margin-bottom:6px;font-size:13px;color:#555;">
        <div><b>${rName}</b></div>
        <div>${linkify(rText)}</div>
      </div>
    `;
  }

  if (isVoice) {
    content += `<div class="voice-message-placeholder" style="display:flex;align-items:center;background:#dcf8c6;padding:8px 12px;border-radius:8px;max-width:240px;"><i class="fas fa-play-circle" style="color:#128c7e;margin-right:8px;"></i><span>Голосовое сообщение (${duration}s)</span></div>`;
  } else if (mediaUrl) {
    content += /\.(mp4|mov|webm)$/i.test(mediaUrl)
      ? `<video controls muted class="chat-media"><source src="${mediaUrl}" type="video/mp4">Видео не поддерживается.</video>`
      : `<img src="${mediaUrl}" class="chat-media" alt="Фото" loading="lazy" />`;
  }

  if (!isVoice && text?.trim()) content += `<div class="message-text">${linkify(text)}</div>`;
  content += `<div class="message-time">${timeStr}<span class="message-checks"><i class="fas fa-check"></i></span></div>`;
  div.innerHTML = content;
  box.appendChild(div);
  box.scrollTop = box.scrollHeight;
}

function updateEditedMessage(messageId, newText, editedAt) {
  const msgEl = document.querySelector(`.message[data-message-id="${messageId}"]`);
  if (!msgEl) return;
  const textEl = msgEl.querySelector('.message-text');
  if (textEl) {
    if (newText.trim()) textEl.innerHTML = linkify(newText);
    else textEl.remove();
  }
  const timeEl = msgEl.querySelector('.message-time');
  if (timeEl) {
    const oldNote = timeEl.querySelector('small');
    if (oldNote) oldNote.remove();
    const date = new Date(editedAt * 1000);
    const timeStr = date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0');
    timeEl.innerHTML = `<small style="font-size:0.8em;opacity:0.7;">• изменено</small> ${timeStr}`;
    if (msgEl.classList.contains('sent')) {
      const checks = msgEl.classList.contains('read')
        ? '<i class="fas fa-check"></i><i class="fas fa-check"></i>'
        : '<i class="fas fa-check"></i>';
      timeEl.innerHTML += `<span class="message-checks">${checks}</span>`;
    }
  }
}

function renderMessages(messages, isGroup, lastReadTimestamp = 0) {
  const box = document.getElementById('messagesBox');
  if (!box) return;
  box.querySelectorAll('.message.optimistic').forEach(el => el.remove());
  const existing = new Map();
  box.querySelectorAll('.message:not(.optimistic)').forEach(el => {
    const id = el.dataset.messageId;
    if (id) existing.set(id, el);
  });

  const serverIds = new Set(messages.map(msg => String(msg.id)));
  existing.forEach((el, id) => { if (!serverIds.has(id)) el.remove(); });

  let lastRenderedDate = null;
  const remaining = box.querySelectorAll('.message:not(.optimistic)');
  if (remaining.length > 0) {
    const lastTs = parseInt(remaining[remaining.length - 1].dataset.timestamp);
    lastRenderedDate = new Date(lastTs * 1000).toLocaleDateString('ru-RU', { year: 'numeric', month: 'long', day: 'numeric' });
  }

  messages.forEach(msg => {
    const msgId = String(msg.id);
    const el = existing.get(msgId);
    if (el && !isGroup && msg.sender === myPhone) {
      const isRead = msg.timestamp <= lastReadTimestamp;
      const checksEl = el.querySelector('.message-checks');
      if (checksEl) {
        checksEl.innerHTML = isRead ? '<i class="fas fa-check"></i><i class="fas fa-check"></i>' : '<i class="fas fa-check"></i>';
        el.classList.toggle('read', isRead);
      }
    }
  });

  messages.forEach(msg => {
    const msgId = String(msg.id);
    let msgEl = existing.get(msgId);
    const msgDate = new Date(msg.timestamp * 1000);
    const msgDay = msgDate.toLocaleDateString('ru-RU', { year: 'numeric', month: 'long', day: 'numeric' });

    if (!msgEl && msgDay !== lastRenderedDate) {
      const div = document.createElement('div');
      div.className = 'message-date-divider';
      div.textContent = msgDay;
      box.appendChild(div);
      lastRenderedDate = msgDay;
    }

    const isOwn = msg.sender === myPhone;
    const isVoiceMessage = msg.mediaUrl && !msg.text?.trim() && /\.(webm|ogg|mp3)$/i.test(msg.mediaUrl);

    // 🔹 Отображение цитаты
    let content = '';
    if (msg.replyTo && (msg.replyText || msg.replySenderName)) {
      const replyName = msg.replySenderName || msg.sender;
      const replyText = msg.replyText || '[Медиа]';
      content += `
        <div class="message-reply" onclick="scrollToMessage(${msg.replyTo})">
          <div><b>${replyName}</b></div>
          <div>${linkify(replyText)}</div>
        </div>
      `;
    }

    if (msgEl) {
      const textEl = msgEl.querySelector('.message-text');
      if (textEl) {
        if (!isVoiceMessage && msg.text?.trim()) textEl.innerHTML = linkify(msg.text);
        else textEl.remove();
      } else if (!isVoiceMessage && msg.text?.trim()) {
        const newTextDiv = document.createElement('div');
        newTextDiv.className = 'message-text';
        newTextDiv.innerHTML = linkify(msg.text);
        const timeEl = msgEl.querySelector('.message-time');
        if (timeEl) msgEl.insertBefore(newTextDiv, timeEl);
      }

      const mediaEl = msgEl.querySelector('.chat-media, .voice-message');
      if (mediaEl) {
        if (mediaEl.tagName === 'IMG' && msg.mediaUrl) mediaEl.src = msg.mediaUrl;
        else if (mediaEl.classList.contains('voice-message') && msg.mediaUrl) {
          const duration = msg.voice_duration || 0;
          mediaEl.querySelector('span').textContent = `Голосовое (${duration || '?'}s)`;
        }
      }

      const timeEl = msgEl.querySelector('.message-time');
      if (timeEl) {
        let timeDisplay = msg.time;
        if (msg.editedAt) timeDisplay = '<small style="font-size:0.8em;opacity:0.7;">• изменено</small> ' + msg.time;
        if (!isGroup && isOwn) {
          const checks = msg.timestamp <= lastReadTimestamp
            ? '<span class="message-checks"><i class="fas fa-check"></i><i class="fas fa-check"></i></span>'
            : '<span class="message-checks"><i class="fas fa-check"></i></span>';
          timeEl.innerHTML = timeDisplay + checks;
        } else timeEl.innerHTML = timeDisplay;
      }
    } else {
      if (!isOwn && isGroup) {
        const namePart = msg.senderName || msg.sender || '';
        const phonePart = msg.sender ? `(${msg.sender})` : '';
        content += `<div class="message-sender">${namePart} ${phonePart}</div>`;
      }

      if (isVoiceMessage) {
        const duration = msg.voice_duration || 0;
        content += `<div class="voice-message" onclick="playVoiceMessage(this, '${msg.mediaUrl}')" style="display:flex;align-items:center;cursor:pointer;padding:8px 12px;border-radius:8px;max-width:240px;background:${isOwn ? '#dcf8c6' : '#ffffff'};"><i class="fas fa-play-circle" style="color:${isOwn ? '#128c7e' : '#333'};margin-right:8px;"></i><span>Голосовое (${duration || '?'}s)</span></div>`;
      } else if (msg.mediaUrl) {
        content += /\.(mp4|mov|webm)$/i.test(msg.mediaUrl)
          ? `<video controls muted class="chat-media"><source src="${msg.mediaUrl}" type="video/mp4">Видео не поддерживается.</video>`
          : `<img src="${msg.mediaUrl}" class="chat-media" alt="Фото" loading="lazy" />`;
      }

      if (!isVoiceMessage && msg.text?.trim()) content += `<div class="message-text">${linkify(msg.text)}</div>`;

      let timeDisplay = msg.time;
      if (msg.editedAt) timeDisplay = '<small style="font-size:0.8em;opacity:0.7;">• изменено</small> ' + msg.time;
      if (!isGroup && isOwn) {
        const checks = msg.timestamp <= lastReadTimestamp
          ? '<span class="message-checks"><i class="fas fa-check"></i><i class="fas fa-check"></i></span>'
          : '<span class="message-checks"><i class="fas fa-check"></i></span>';
        content += `<div class="message-time">${timeDisplay}${checks}</div>`;
      } else content += `<div class="message-time">${timeDisplay}</div>`;

      const div = document.createElement('div');
      div.className = `message ${isOwn ? 'sent' : 'received'}`;
      if (!isGroup && isOwn && msg.timestamp <= lastReadTimestamp) div.classList.add('read');
      div.dataset.timestamp = msg.timestamp;
      div.dataset.messageId = msg.id;
      div.innerHTML = content;
      box.appendChild(div);
    }
  });

  if (messages.some(msg => !existing.has(String(msg.id)))) box.scrollTop = box.scrollHeight;
}

async function loadChatMessages() {
  let messages = [], lastReadTimestamp = 0, chatId = '';
  try {
    if (isGroupChat) {
      const res = await fetch(`api/get_group_chat.php?group=${encodeURIComponent(currentGroupId)}&t=${Date.now()}`);
      const data = await res.json();
      messages = data.messages || [];
      chatId = currentGroupId;
    } else {
      const res = await fetch(`api/get_chat.php?them=${encodeURIComponent(currentChatPhone)}&t=${Date.now()}`);
      const data = await res.json();
      messages = data.messages || [];
      lastReadTimestamp = data.lastReadTimestamp || 0;
      chatId = getChatId(myPhone, currentChatPhone);
    }

    let hasNewIncoming = false, newMaxTs = lastMessageTimestamp;
    for (const msg of messages) {
      if (msg.sender !== myPhone && msg.timestamp > lastMessageTimestamp) {
        hasNewIncoming = true;
        if (msg.timestamp > newMaxTs) newMaxTs = msg.timestamp;
      }
    }

    if (hasNewIncoming && isChatScreenActive && typeof playMessageSound === 'function') {
      playMessageSound();
      lastMessageTimestamp = newMaxTs;
    }

    renderMessages(messages, isGroupChat, lastReadTimestamp);
    markChatAsRead(chatId, messages);
  } catch (e) { console.error('Ошибка загрузки сообщений:', e); }
}

async function openChat(phone, name) {
  if (!phone || !name) return;
  isGroupChat = false; currentGroupId = null; currentChatPhone = phone; currentChatName = name;
  document.getElementById('chatName').innerText = name;
  if (window.lastNotifiedTimestamps) window.lastNotifiedTimestamps[phone] = 0;

  const avatarDiv = document.getElementById('chatAvatar');
  avatarDiv.textContent = name.charAt(0).toUpperCase();
  avatarDiv.style.display = 'flex';

  const user = await window.userCache.fetchWithCache(phone);
  if (user?.avatar) {
    const img = document.createElement('img');
    img.src = user.avatar + '?t=' + Date.now();
    img.className = 'avatar-img'; img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;';
    avatarDiv.innerHTML = ''; avatarDiv.appendChild(img);
  } else avatarDiv.textContent = name.charAt(0).toUpperCase();

  updateChatHeaderStatusFromList(phone);
  document.getElementById('messagesBox').innerHTML = '<div class="loading">Загрузка...</div>';
  lastMessageTimestamp = 0; isFirstLoad = true;
  loadChatMessages(); showScreen('chatScreen');
}

function openGroupChat(groupId, name) {
  if (!groupId || !name) return;
  isGroupChat = true; currentGroupId = groupId; currentChatPhone = null; currentChatName = name;
  document.getElementById('chatName').innerText = name;
  if (window.lastNotifiedTimestamps) window.lastNotifiedTimestamps[groupId] = 0;

  const avatar = document.getElementById('chatAvatar');
  avatar.textContent = name.charAt(0).toUpperCase(); avatar.style.display = 'flex';
  const dot = document.getElementById('chatStatusDot');
  if (dot) dot.style.display = 'none';
  document.getElementById('messagesBox').innerHTML = '<div class="loading">Загрузка...</div>';
  lastMessageTimestamp = 0; isFirstLoad = true;
  loadChatMessages(); showScreen('chatScreen');
}

document.addEventListener('DOMContentLoaded', () => {
  try { localStorage.setItem('chatUser', JSON.stringify({ phone: myPhone, name: myName })); } catch (e) {}
  showScreen('chatListScreen');

  document.addEventListener('click', (e) => {
    const menu = document.getElementById('menuPopup');
    if (!e.target.closest('.header') && menu && menu.style.display === 'block') menu.style.display = 'none';
  });

  document.addEventListener('click', (e) => {
    if (e.target.closest('#chatAvatar') || e.target.closest('#chatName')) {
      if (typeof openContactInfo === 'function') openContactInfo(isGroupChat ? null : currentChatPhone, currentChatName, isGroupChat, isGroupChat ? currentGroupId : null);
    }
  });

  const input = document.getElementById('messageInput');
  if (input) input.addEventListener('focus', () => {
    const box = document.getElementById('messagesBox');
    if (box) setTimeout(() => box.scrollTop = box.scrollHeight, 300);
  });

  const getChatId = () => isGroupChat ? currentGroupId : currentChatPhone;
  const getCurrentIsGroup = () => isGroupChat;
  if (typeof attachMessageDeleteHandlers === 'function') attachMessageDeleteHandlers(myPhone, getChatId, getCurrentIsGroup);
});

function toggleMenu() {
  const menu = document.getElementById('menuPopup');
  menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

function uploadAvatar() {
  if (typeof initAvatarUpload === 'function') initAvatarUpload(myPhone);
  const menu = document.getElementById('menuPopup');
  if (menu) menu.style.display = 'none';
}

function showSearch() {
  const input = document.getElementById('searchInput'), results = document.getElementById('searchResults');
  if (input) input.value = ''; if (results) results.innerHTML = '';
  showScreen('searchScreen'); setTimeout(() => { if (input) input.focus(); }, 100);
}

function backToChats() {
  stopAutoUpdate(); showScreen('chatListScreen'); isFirstLoad = true;
  // 🔹 Сбрасываем цитату при выходе из чата
  cancelReply();
}

function logout() {
  stopAutoUpdate();
  if (typeof stopGlobalChatUpdates === 'function') stopGlobalChatUpdates();
  if (typeof stopOnlinePing === 'function') stopOnlinePing();
  if (myPhone && typeof sendOfflineStatus === 'function') try { sendOfflineStatus(myPhone); } catch (e) {}

  if (window.userCache) window.userCache.invalidateAll();
  lastUnreadCount = {}; lastMessageTimestamp = 0;
  if (window.lastNotifiedTimestamps) window.lastNotifiedTimestamps = {};
  isFirstLoad = true; if (window.removeMediaPreview) window.removeMediaPreview();

  myPhone = ''; myName = ''; currentChatPhone = ''; currentChatName = ''; currentGroupId = null; isGroupChat = false;
  try { localStorage.removeItem('chatUser'); } catch (e) {}

  fetch('./api/logout.php', { method: 'POST', headers: { 'Content-Type': 'application/json' } }).finally(() => {
    window.location.replace('./index.php');
  });
}

function linkify(text) {
  if (!text || typeof text !== 'string') return '';
  const urlRegex = /(\b(https?:\/\/|www\.)[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*))/gi;
  return text.replace(urlRegex, url => {
    const full = url.startsWith('http') ? url : 'https://' + url;
    return `<a href="${full}" target="_blank" rel="noopener noreferrer">${url}</a>`;
  });
}

['openContactInfo','openMediaWithContact','muteNotifications','searchInChat','showEncryptionInfo','clearChat','blockContact','reportContact']
  .forEach(fn => { if (typeof window[fn] === 'undefined') window[fn] = (...args) => console.warn(`${fn} not implemented yet`, args); });

if (typeof window.sendMessage === 'undefined') window.sendMessage = () => console.warn('sendMessage not loaded yet');
if (typeof window.updateSelectedParticipants === 'undefined') window.updateSelectedParticipants = () => console.warn('updateSelectedParticipants not loaded yet');

function openOwnProfile() { openContactInfo(myPhone, myName, false, null); }

window.playVoiceMessage = () => console.warn('playVoiceMessage called before voice-message.js loaded');

function toggleMobileMode() {
  const isMobile = localStorage.getItem('forceMobile') === 'true';
  const newMode = !isMobile;
  localStorage.setItem('forceMobile', String(newMode));
  document.body.classList.toggle('force-mobile', newMode);
}

document.addEventListener('DOMContentLoaded', () => {
  if (localStorage.getItem('forceMobile') === 'true') document.body.classList.add('force-mobile');
});
</script>

<script src="user-cache.js" defer></script>
<script src="sound.js" defer></script>
<script src="online-status.js" defer></script>
<script src="emoji-picker.js" defer></script>
<script src="mediamanager.js" defer></script>
<script src="zuummedia.js" defer></script>
<script src="message-delete.js" defer></script>
<script src="avatar-manager.js" defer></script>
<script src="chat-sender.js" defer></script>
<script src="search.js" defer></script>
<script src="chat-list-manager.js" defer></script>
<script src="contact-info.js" defer></script>
<script src="voice-message.js" defer></script>
<script src="background-updates.js" defer></script>

</body>
</html>