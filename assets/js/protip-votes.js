// Initialize vote handling when the DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  const voteButtons = document.querySelectorAll('.ptv-card__button');

  voteButtons.forEach((button) => {
    button.addEventListener('click', handleVoteClick);
  });

  const refreshButton = document.querySelector('.ptv-refresh');

    if (refreshButton) {
    refreshButton.addEventListener('click', handleRefreshClick);
    }
});

function setButtonLoading(button, isLoading) {
  button.disabled = isLoading;
  button.textContent = isLoading ? 'Saving...' : 'Vote for this tip';
}

async function handleVoteClick(event) {
  const button = event.currentTarget;
  const protipId = button.dataset.protipId;
  const card = button.closest('.ptv-card');
  const message = card.querySelector('.ptv-card__message');

  setButtonLoading(button, true);

  try {
    const result = await submitVote(protipId);
//uses textContent, not innerHTML, because the message may come from PHP and should be treated as text.
   message.textContent = `${result.data.message} Total votes: ${result.data.vote_count}`;
    button.textContent = 'Voted';
    button.disabled = true;
    card.classList.add('ptv-card--voted');
  } catch (error) {
    message.textContent = error.message;
    setButtonLoading(button, false);
  }
  console.log('Vote button clicked for protip ID:', protipId);
}

async function submitVote(protipId) {
  const formData = new FormData();

  formData.append('action', 'protip_vote');
  formData.append('nonce', ProtipVotes.nonce);
  formData.append('protip_id', protipId);

  const response = await fetch(ProtipVotes.ajaxUrl, {
    method: 'POST',
    body: formData,
  });

  const result = await response.json();

  if (!response.ok || !result.success) {
    throw new Error(result.data?.message || 'Something went wrong.');
  }

  return result;
}

async function fetchLatestProtips() {
  const response = await fetch(ProtipVotes.restUrl, {
  method: 'GET',
  headers: {
    'X-WP-Nonce': ProtipVotes.restNonce,
  },
});

  const result = await response.json();

  if (!response.ok) {
    throw new Error('Could not load latest pro-tips.');
  }

  return result;
}

async function handleRefreshClick() {
  const refreshButton = document.querySelector('.ptv-refresh');

  if (refreshButton) {
    refreshButton.disabled = true;
    refreshButton.textContent = 'Checking...';
  }

  try {
    const result = await fetchLatestProtips();

    updateVoteCounts(result.items);

    if (refreshButton) {
      refreshButton.textContent = 'Vote counts updated';
    }
  } catch (error) {
    console.error(error.message);

    if (refreshButton) {
      refreshButton.textContent = 'Could not update votes';
    }
  } finally {
    setTimeout(() => {
      if (refreshButton) {
        refreshButton.disabled = false;
        refreshButton.textContent = 'Check latest vote counts';
      }
    }, 1500);
  }
}

function updateVoteCounts(items) {
  if (!Array.isArray(items)) {
    return;
  }

  items.forEach((item) => {
    const card = document.querySelector(`.ptv-card[data-protip-id="${item.id}"]`);

    if (!card) {
      return;
    }

    const message = card.querySelector('.ptv-card__message');

    if (!message) {
      return;
    }

    message.textContent = `Votes: ${item.vote_count}`;
  });
}


