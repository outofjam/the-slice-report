import client from './client'

export const getRatings = (googlePlaceId) =>
  client.get(`/places/${googlePlaceId}/ratings`)

export const upsertRating = (googlePlaceId, data) =>
  client.post(`/places/${googlePlaceId}/ratings`, data)

export const deleteRating = (googlePlaceId) =>
  client.delete(`/places/${googlePlaceId}/ratings`)
