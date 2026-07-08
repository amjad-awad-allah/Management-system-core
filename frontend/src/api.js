const API_BASE_URL = 'http://127.0.0.1:8001/api/v1/nachhilfe';
const TOKEN = '1|fZ0FaTGXy6uzUlgiTFhn4cGw3rdCsZOjQZ1BFYCce48da968';

export const api = {
  async get(endpoint) {
    try {
      const response = await fetch(`${API_BASE_URL}${endpoint}`, {
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${TOKEN}`
        }
      });
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      return await response.json();
    } catch (error) {
      console.error('API GET Error:', error);
      throw error;
    }
  },

  async post(endpoint, data) {
    try {
      const response = await fetch(`${API_BASE_URL}${endpoint}`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${TOKEN}`
        },
        body: JSON.stringify(data)
      });
      
      const responseData = await response.json();
      if (!response.ok) {
          throw new Error(responseData.message || `HTTP error! status: ${response.status}`);
      }
      return responseData;
    } catch (error) {
      console.error('API POST Error:', error);
      throw error;
    }
  }
};
