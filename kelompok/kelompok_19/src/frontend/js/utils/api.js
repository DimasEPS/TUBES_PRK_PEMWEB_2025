// API Utility Module - Simple fetch wrapper

function apiRequest(endpoint, options = {}) {
  const url = API_URL + endpoint;

  const defaultOptions = {
    credentials: "include",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
  };

  const config = {
    ...defaultOptions,
    ...options,
    headers: {
      ...defaultOptions.headers,
      ...options.headers,
    },
  };

  return fetch(url, config)
    .then((response) => {
      if (response.status === 401) {
        window.location.href = "../login.html";
        return;
      }
      return response.json();
    })
    .catch((error) => {
      console.error("API Error:", error);
      throw error;
    });
}

function apiGet(endpoint) {
  return apiRequest(endpoint, { method: "GET" });
}

function apiPost(endpoint, data) {
  const body = new URLSearchParams(data).toString();
  return apiRequest(endpoint, {
    method: "POST",
    body: body,
  });
}

function apiPostFile(endpoint, formData) {
  return fetch(API_URL + endpoint, {
    method: "POST",
    credentials: "include",
    body: formData,
  })
    .then((response) => {
      if (response.status === 401) {
        window.location.href = "../login.html";
        return;
      }
      return response.json();
    })
    .catch((error) => {
      console.error("API Error:", error);
      throw error;
    });
}

function apiPatch(endpoint, data) {
  const body = new URLSearchParams(data).toString();
  return apiRequest(endpoint, {
    method: "PATCH",
    body: body,
  });
}
