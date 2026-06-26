import client from './client'

export const getPlaces = (city) =>
  client.get('/places', { params: city ? { city } : {} })

export const addPlace = (data) =>
  client.post('/places', data)
