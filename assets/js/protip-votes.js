// Initialize vote handling when the DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  const voteButtons = document.querySelectorAll('.ptv-card__button');

  voteButtons.forEach((button) => {
    button.addEventListener('click', handleVoteClick);
  });
//// Find the refresh button and attach a click handler if it exists.
  const refreshButton = document.querySelector('.ptv-refresh');

    if (refreshButton) {
    refreshButton.addEventListener('click', handleRefreshClick);
    }
});

function setButtonLoading(button, isLoading) {
  button.disabled = isLoading;
  button.textContent = isLoading ? 'Saving...' : 'Vote for this tip';
}
//async function will return a Promise, and inside it I can use await to pause until asynchronous work finishes.
async function handleVoteClick(event) {
  const button = event.currentTarget;
  const protipId = button.dataset.protipId;
  const card = button.closest('.ptv-card');
  const message = card.querySelector('.ptv-card__message');

  setButtonLoading(button, true);

  try {
    //await can only be used inside an async function, and it pauses the execution of the function until the Promise is resolved or rejected. 
    // In this case, it waits for the submitVote function to complete before proceeding to update the UI with the result.
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
  //Create a form-like data object
  const formData = new FormData();
//add the AJAX action, nonce, and pro-tip ID, then send it to WordPress
  formData.append('action', 'protip_vote');
  formData.append('nonce', ProtipVotes.nonce);
  formData.append('protip_id', protipId);

  const response = await fetch(ProtipVotes.ajaxUrl, {
    method: 'POST',
    body: formData,
  });
//Wait for the server response body to be converted from JSON into a JavaScript object, and store it in result. 
//This allows us to easily access properties like result.success and result.data.message in the subsequent code.
  const result = await response.json();

  if (!response.ok || !result.success) {
    throw new Error(result.data?.message || 'Something went wrong.');
  }

  return result;
}

async function fetchLatestProtips() {
  //fetch() is the JavaScript function that sends a request to a URL
  const response = await fetch(ProtipVotes.restUrl, {
  method: 'GET', //only want to retrieve/read data
  headers: {
    'X-WP-Nonce': ProtipVotes.restNonce, //X-WP-Nonce is the header name WordPress expects for REST API nonce checks
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
//try = attempt to fetch latest pro-tips and update vote counts.
  try {
    const result = await fetchLatestProtips();

    updateVoteCounts(result.items);

    if (refreshButton) {
      refreshButton.textContent = 'Vote counts updated';
    }
    //catch = if something goes wrong, show/log the error.
  } catch (error) {
    console.error(error.message);

    if (refreshButton) {
      refreshButton.textContent = 'Could not update votes';
    }
  //finally = after success or failure, reset the button after 1.5 seconds.
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
  //expects items to be an array of Pro-tip objects from the REST API
  if (!Array.isArray(items)) {
    //If it is not an array, the function stops. This prevents errors if the API response is unexpected.
    return;
  }
//looping through each Pro-tip item
  items.forEach((item) => {
    //For each item, trying to find the matching card on the page
    const card = document.querySelector(`.ptv-card[data-protip-id="${item.id}"]`);
    //If it can’t find a matching card on this page, skip this item and move to the next one
    if (!card) {
      return;
    }
//looking inside that card for the vote message
    const message = card.querySelector('.ptv-card__message');
//if there is no message element, skip it and move to the next item. This prevents errors if the card structure is not as expected.
    if (!message) {
      return;
    }
//update the text to show the newest vote count
    message.textContent = `Votes: ${item.vote_count}`;
  });
}


