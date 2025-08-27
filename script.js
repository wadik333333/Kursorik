/* Stories Demo - Instagram-like viewer */

const STORIES_DATA = [
  {
    userId: 'u-alina',
    username: 'Alina',
    avatarUrl:
      'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=200&q=60',
    items: [
      {
        type: 'image',
        url: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80',
        durationMs: 4500,
      },
      {
        type: 'video',
        url: 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
      },
      {
        type: 'image',
        url: 'https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=1200&q=80',
        durationMs: 5000,
      },
    ],
  },
  {
    userId: 'u-max',
    username: 'Max',
    avatarUrl:
      'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=200&q=60',
    items: [
      {
        type: 'image',
        url: 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=80',
        durationMs: 5000,
      },
      {
        type: 'image',
        url: 'https://images.unsplash.com/photo-1470770903676-69b98201ea1c?auto=format&fit=crop&w=1200&q=80',
        durationMs: 4500,
      },
      {
        type: 'video',
        url: 'https://samplelib.com/lib/preview/mp4/sample-10s.mp4',
      },
    ],
  },
  {
    userId: 'u-yuki',
    username: 'Yuki',
    avatarUrl:
      'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=200&q=60',
    items: [
      {
        type: 'image',
        url: 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308?auto=format&fit=crop&w=1200&q=80',
        durationMs: 4000,
      },
      {
        type: 'image',
        url: 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?auto=format&fit=crop&w=1200&q=80',
        durationMs: 5000,
      },
      {
        type: 'image',
        url: 'https://images.unsplash.com/photo-1549880338-65ddcdfd017b?auto=format&fit=crop&w=1200&q=80',
        durationMs: 4500,
      },
    ],
  },
  {
    userId: 'u-anna',
    username: 'Anna',
    avatarUrl:
      'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&q=60',
    items: [
      {
        type: 'video',
        url: 'https://samplelib.com/lib/preview/mp4/sample-5s.mp4',
      },
      {
        type: 'image',
        url: 'https://images.unsplash.com/photo-1503023345310-bd7c1de61c7d?auto=format&fit=crop&w=1200&q=80',
        durationMs: 5000,
      },
    ],
  },
  {
    userId: 'u-maria',
    username: 'Maria',
    avatarUrl:
      'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=200&q=60',
    items: [
      {
        type: 'image',
        url: 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=1200&q=80',
        durationMs: 4500,
      },
      {
        type: 'image',
        url: 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?auto=format&fit=crop&w=1200&q=80',
        durationMs: 4500,
      },
      {
        type: 'video',
        url: 'https://samplelib.com/lib/preview/mp4/sample-10s.mp4',
      },
    ],
  },
];

const DEFAULT_IMAGE_DURATION_MS = 5000;

const elements = {
  storiesBar: document.getElementById('storiesBar'),
  viewer: document.getElementById('storyViewer'),
  progress: document.getElementById('progress'),
  viewerAvatar: document.getElementById('viewerAvatar'),
  viewerUsername: document.getElementById('viewerUsername'),
  closeViewer: document.getElementById('closeViewer'),
  muteToggle: document.getElementById('muteToggle'),
  muteIcon: document.getElementById('muteIcon'),
  stage: document.getElementById('viewerStage'),
  img: document.getElementById('storyImage'),
  video: document.getElementById('storyVideo'),
  tapPrev: document.getElementById('tapPrev'),
  tapNext: document.getElementById('tapNext'),
};

let state = {
  currentStoryIndex: null,
  currentItemIndex: null,
  isOpen: false,
  isPaused: false,
  isVideoMuted: true,
  rafId: null,
  itemStartTs: 0,
  pausedAtTs: 0,
  currentItemDurationMs: DEFAULT_IMAGE_DURATION_MS,
};

function renderStoriesBar() {
  elements.storiesBar.innerHTML = '';
  STORIES_DATA.forEach((story, storyIndex) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'story-chip';
    wrapper.setAttribute('role', 'listitem');

    const button = document.createElement('button');
    button.className = 'chip-button';
    button.addEventListener('click', () => openViewer(storyIndex, 0));

    const ring = document.createElement('div');
    ring.className = 'ring' + (story.seen ? ' seen' : '');

    const img = document.createElement('img');
    img.src = story.avatarUrl;
    img.alt = `${story.username} avatar`;

    ring.appendChild(img);
    button.appendChild(ring);

    const name = document.createElement('div');
    name.className = 'chip-username';
    name.textContent = story.username;

    wrapper.appendChild(button);
    wrapper.appendChild(name);
    elements.storiesBar.appendChild(wrapper);

    // Preload first item image for smoother open
    const firstItem = story.items[0];
    if (firstItem && firstItem.type === 'image') {
      const preload = new Image();
      preload.src = firstItem.url;
    }
  });
}

function openViewer(storyIndex, itemIndex) {
  state.currentStoryIndex = storyIndex;
  state.currentItemIndex = itemIndex;
  state.isOpen = true;
  state.isPaused = false;
  state.isVideoMuted = true;
  updateMuteUi();

  const story = STORIES_DATA[storyIndex];
  elements.viewerAvatar.src = story.avatarUrl;
  elements.viewerUsername.textContent = story.username;
  elements.viewer.classList.remove('hidden');
  elements.viewer.setAttribute('aria-hidden', 'false');

  buildSegments();
  loadCurrentItem();
  bindGlobalShortcuts();
}

function closeViewer() {
  cancelAnimationFrame(state.rafId);
  state.rafId = null;
  state.isOpen = false;
  elements.viewer.classList.add('hidden');
  elements.viewer.setAttribute('aria-hidden', 'true');
  elements.video.pause();
  unbindGlobalShortcuts();
}

function buildSegments() {
  const { currentStoryIndex } = state;
  const story = STORIES_DATA[currentStoryIndex];
  elements.progress.innerHTML = '';
  story.items.forEach(() => {
    const segment = document.createElement('div');
    segment.className = 'segment';
    const fill = document.createElement('div');
    fill.className = 'fill';
    segment.appendChild(fill);
    elements.progress.appendChild(segment);
  });
}

function getCurrentItem() {
  const story = STORIES_DATA[state.currentStoryIndex];
  return story.items[state.currentItemIndex];
}

function loadCurrentItem() {
  cancelAnimationFrame(state.rafId);
  state.rafId = null;
  state.isPaused = false;

  const item = getCurrentItem();
  const isVideo = item.type === 'video';
  const segments = elements.progress.querySelectorAll('.segment .fill');
  segments.forEach((fill, idx) => {
    fill.style.width = idx < state.currentItemIndex ? '100%' : '0%';
  });

  if (isVideo) {
    elements.img.style.display = 'none';
    elements.video.style.display = 'block';
    elements.video.muted = state.isVideoMuted;
    elements.video.src = item.url;
    elements.video.currentTime = 0;
    elements.video.play().catch(() => {});
    // Duration will be set after metadata
    elements.video.onloadedmetadata = () => {
      state.currentItemDurationMs = Math.max(
        1000,
        (elements.video.duration || 5) * 1000
      );
      startTicker();
    };
    elements.video.onended = () => goNext();
  } else {
    elements.video.pause();
    elements.video.removeAttribute('src');
    elements.video.load();
    elements.video.style.display = 'none';
    elements.img.style.display = 'block';
    elements.img.src = item.url;
    state.currentItemDurationMs = item.durationMs || DEFAULT_IMAGE_DURATION_MS;
    startTicker();
    // Preload next image
    const next = getNextItem();
    if (next && next.type === 'image') {
      const preload = new Image();
      preload.src = next.url;
    }
  }
}

function startTicker() {
  state.itemStartTs = performance.now();
  state.pausedAtTs = 0;
  tick();
}

function tick() {
  if (!state.isOpen) return;
  const now = performance.now();
  const elapsed = now - state.itemStartTs;
  const duration = state.currentItemDurationMs;
  const progress = Math.min(1, Math.max(0, elapsed / duration));
  updateProgressUi(progress);
  if (progress >= 1) {
    goNext();
    return;
  }
  if (!state.isPaused) {
    state.rafId = requestAnimationFrame(tick);
  }
}

function updateProgressUi(progress01) {
  const fills = elements.progress.querySelectorAll('.segment .fill');
  fills.forEach((fill, idx) => {
    if (idx < state.currentItemIndex) {
      fill.style.width = '100%';
    } else if (idx === state.currentItemIndex) {
      fill.style.width = `${progress01 * 100}%`;
    } else {
      fill.style.width = '0%';
    }
  });
}

function pause() {
  if (state.isPaused) return;
  state.isPaused = true;
  state.pausedAtTs = performance.now();
  elements.video.pause();
}

function resume() {
  if (!state.isPaused) return;
  state.isPaused = false;
  if (state.pausedAtTs) {
    const pauseDelta = performance.now() - state.pausedAtTs;
    state.itemStartTs += pauseDelta;
  }
  if (getCurrentItem().type === 'video') {
    elements.video.play().catch(() => {});
  }
  state.rafId = requestAnimationFrame(tick);
}

function goNext() {
  const story = STORIES_DATA[state.currentStoryIndex];
  if (state.currentItemIndex < story.items.length - 1) {
    state.currentItemIndex += 1;
    loadCurrentItem();
    return;
  }
  // End of story -> mark seen and go to next story if any
  story.seen = true;
  renderStoriesBar();
  if (state.currentStoryIndex < STORIES_DATA.length - 1) {
    state.currentStoryIndex += 1;
    state.currentItemIndex = 0;
    // Keep viewer open and continue
    const nextStory = STORIES_DATA[state.currentStoryIndex];
    elements.viewerAvatar.src = nextStory.avatarUrl;
    elements.viewerUsername.textContent = nextStory.username;
    buildSegments();
    loadCurrentItem();
  } else {
    closeViewer();
  }
}

function goPrev() {
  if (state.currentItemIndex > 0) {
    state.currentItemIndex -= 1;
    loadCurrentItem();
    return;
  }
  if (state.currentStoryIndex > 0) {
    state.currentStoryIndex -= 1;
    const prevStory = STORIES_DATA[state.currentStoryIndex];
    state.currentItemIndex = prevStory.items.length - 1;
    elements.viewerAvatar.src = prevStory.avatarUrl;
    elements.viewerUsername.textContent = prevStory.username;
    buildSegments();
    loadCurrentItem();
  } else {
    closeViewer();
  }
}

function getNextItem() {
  const story = STORIES_DATA[state.currentStoryIndex];
  const nextIndex = state.currentItemIndex + 1;
  return story.items[nextIndex];
}

function bindGlobalShortcuts() {
  document.addEventListener('keydown', onKeyDown);
  document.addEventListener('visibilitychange', onVisibilityChange);
}

function unbindGlobalShortcuts() {
  document.removeEventListener('keydown', onKeyDown);
  document.removeEventListener('visibilitychange', onVisibilityChange);
}

function onKeyDown(e) {
  if (!state.isOpen) return;
  if (e.key === 'ArrowRight') {
    goNext();
  } else if (e.key === 'ArrowLeft') {
    goPrev();
  } else if (e.key === 'Escape') {
    closeViewer();
  } else if (e.key.toLowerCase() === 'm') {
    toggleMute();
  } else if (e.key === ' ') {
    if (state.isPaused) resume(); else pause();
  }
}

function onVisibilityChange() {
  if (!state.isOpen) return;
  if (document.hidden) pause(); else resume();
}

function toggleMute() {
  state.isVideoMuted = !state.isVideoMuted;
  elements.video.muted = state.isVideoMuted;
  updateMuteUi();
}

function updateMuteUi() {
  elements.muteIcon.textContent = state.isVideoMuted ? '🔇' : '🔊';
}

// Stage interactions: tap zones and long-press pause
function bindStageInteractions() {
  let holdTimeout = null;
  let isHolding = false;
  let touchStartX = 0;
  let touchDeltaX = 0;

  const startHold = () => {
    if (isHolding) return;
    isHolding = true;
    pause();
  };
  const endHold = () => {
    if (!isHolding) return;
    isHolding = false;
    resume();
  };

  elements.stage.addEventListener('mousedown', () => {
    holdTimeout = setTimeout(startHold, 120);
  });
  elements.stage.addEventListener('mouseup', () => {
    clearTimeout(holdTimeout);
    if (isHolding) endHold();
  });
  elements.stage.addEventListener('mouseleave', () => {
    clearTimeout(holdTimeout);
    if (isHolding) endHold();
  });

  // Touch for pause/hold and swipe
  elements.stage.addEventListener('touchstart', (e) => {
    if (e.touches && e.touches[0]) touchStartX = e.touches[0].clientX;
    touchDeltaX = 0;
    holdTimeout = setTimeout(startHold, 120);
  }, { passive: true });
  elements.stage.addEventListener('touchmove', (e) => {
    if (e.touches && e.touches[0]) {
      touchDeltaX = e.touches[0].clientX - touchStartX;
    }
  }, { passive: true });
  elements.stage.addEventListener('touchend', () => {
    clearTimeout(holdTimeout);
    if (isHolding) endHold();
    const threshold = 60;
    if (touchDeltaX > threshold) {
      goPrev();
    } else if (touchDeltaX < -threshold) {
      goNext();
    }
  });

  elements.tapPrev.addEventListener('click', () => goPrev());
  elements.tapNext.addEventListener('click', () => goNext());
}

// Control buttons
elements.closeViewer.addEventListener('click', closeViewer);
elements.muteToggle.addEventListener('click', toggleMute);

renderStoriesBar();
bindStageInteractions();

