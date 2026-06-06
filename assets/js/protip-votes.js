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
  try {
    const result = await fetchLatestProtips();

    console.log('Latest pro-tips from REST API:', result);
  } catch (error) {
    console.error(error.message);
  }
}


