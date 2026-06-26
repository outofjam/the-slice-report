import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  CartesianGrid,
  ResponsiveContainer,
  Scatter,
  ScatterChart,
  Tooltip,
  XAxis,
  YAxis,
  ZAxis,
} from 'recharts'
import { logout as apiLogout } from '../api/auth'
import { addPlace, getPlaces } from '../api/places'
import { deleteRating, upsertRating } from '../api/ratings'
import { useAuth } from '../context/AuthContext'

// ─── Google Maps loader ────────────────────────────────────────────────────

let mapsPromise = null

function loadGoogleMaps() {
  if (mapsPromise) return mapsPromise
  mapsPromise = new Promise((resolve, reject) => {
    if (window.google?.maps?.places) {
      resolve()
      return
    }
    const script = document.createElement('script')
    script.src = `https://maps.googleapis.com/maps/api/js?key=${import.meta.env.VITE_GOOGLE_MAPS_KEY}&libraries=places`
    script.async = true
    script.onload = resolve
    script.onerror = reject
    document.head.appendChild(script)
  })
  return mapsPromise
}

function cityFromComponents(components) {
  const order = ['locality', 'sublocality', 'administrative_area_level_2', 'administrative_area_level_1']
  for (const type of order) {
    const component = components.find((c) => c.types.includes(type))
    if (component) return component.long_name
  }
  return ''
}

// ─── Hype Index helpers ────────────────────────────────────────────────────

function hypeDotColor(hypeIndex) {
  if (hypeIndex === null || hypeIndex === undefined) return '#94a3b8'
  if (hypeIndex > 0.5) return '#ef4444'   // overhyped
  if (hypeIndex < -0.5) return '#22c55e'  // underrated
  return '#94a3b8'                         // consensus
}

function hypeLabel(hypeIndex) {
  if (hypeIndex === null || hypeIndex === undefined) return null
  if (hypeIndex > 0.5) return 'Overhyped'
  if (hypeIndex < -0.5) return 'Underrated'
  return 'Consensus'
}

// ─── Scatter chart helpers ─────────────────────────────────────────────────

function ScatterDot({ cx, cy, payload, activeId, onSelect }) {
  const isActive = payload.id === activeId
  const fill = payload.myRating ? '#f97316' : hypeDotColor(payload.hypeIndex)
  return (
    <circle
      cx={cx}
      cy={cy}
      r={isActive ? 8 : 5}
      fill={fill}
      stroke={isActive ? '#1e293b' : 'transparent'}
      strokeWidth={2}
      style={{ cursor: 'pointer' }}
      onClick={() => onSelect(payload.id)}
    />
  )
}

function ChartTooltip({ active, payload }) {
  if (!active || !payload?.length) return null
  const d = payload[0].payload
  const label = hypeLabel(d.hypeIndex)
  return (
    <div className="bg-white border border-gray-200 rounded-lg shadow-lg px-3 py-2 text-sm pointer-events-none min-w-40">
      <p className="font-semibold text-gray-900">{d.name}</p>
      {d.city && <p className="text-xs text-gray-400 mb-1">{d.city}</p>}
      <p className="text-gray-700">Avg rating <span className="font-medium">{d.y} / 5</span></p>
      {d.googleRating && (
        <p className="text-gray-700">Google rating <span className="font-medium">{d.googleRating} / 5</span></p>
      )}
      {d.hypeIndex !== null && d.hypeIndex !== undefined && (
        <p className="text-gray-700">
          Hype index{' '}
          <span className={`font-medium ${d.hypeIndex > 0.5 ? 'text-red-500' : d.hypeIndex < -0.5 ? 'text-green-500' : 'text-gray-500'}`}>
            {d.hypeIndex > 0 ? '+' : ''}{d.hypeIndex}
          </span>
          {label && <span className="text-gray-400 ml-1">({label})</span>}
        </p>
      )}
      <p className="text-gray-700 mt-1">Avg price <span className="font-medium">${d.x}</span></p>
      <p className="text-xs text-gray-400 mt-0.5">{d.count} {d.count === 1 ? 'rating' : 'ratings'}</p>
    </div>
  )
}

// ─── Add place modal ───────────────────────────────────────────────────────

function AddPlaceModal({ onClose, onAdded }) {
  const inputRef = useRef(null)
  const autocompleteRef = useRef(null)
  const [mapsReady, setMapsReady] = useState(false)
  const [mapsError, setMapsError] = useState(false)
  const [selected, setSelected] = useState(null)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  useEffect(() => {
    loadGoogleMaps()
      .then(() => setMapsReady(true))
      .catch(() => setMapsError(true))
  }, [])

  useEffect(() => {
    if (!mapsReady || !inputRef.current) return

    autocompleteRef.current = new window.google.maps.places.Autocomplete(inputRef.current, {
      types: ['establishment'],
      fields: ['place_id', 'name', 'formatted_address', 'address_components', 'geometry', 'rating'],
    })

    autocompleteRef.current.addListener('place_changed', () => {
      const place = autocompleteRef.current.getPlace()
      if (!place.place_id) return

      setSelected({
        google_place_id: place.place_id,
        name: place.name,
        address: place.formatted_address ?? '',
        city: cityFromComponents(place.address_components ?? []),
        lat: place.geometry?.location?.lat() ?? null,
        lng: place.geometry?.location?.lng() ?? null,
        currency: 'USD',
        google_rating: place.rating ?? null,
      })
    })

    return () => window.google.maps.event.clearInstanceListeners(autocompleteRef.current)
  }, [mapsReady])

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!selected) return
    setSaving(true)
    setError(null)
    try {
      await addPlace(selected)
      onAdded()
      onClose()
    } catch (err) {
      setError(err.response?.data?.message ?? 'Failed to add place.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div
      className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
      onClick={onClose}
    >
      <div
        className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6"
        onClick={(e) => e.stopPropagation()}
      >
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Add a pizza place</h2>

        {mapsError ? (
          <p className="text-sm text-red-600">
            Google Maps failed to load. Check your API key in <code>client/.env</code>.
          </p>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs text-gray-500 mb-1">Search for a place</label>
              <input
                ref={inputRef}
                type="text"
                placeholder={mapsReady ? 'Start typing a pizza place…' : 'Loading Maps…'}
                disabled={!mapsReady}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 disabled:bg-gray-50 disabled:text-gray-400"
              />
            </div>

            {selected && (
              <div className="bg-orange-50 border border-orange-200 rounded-lg px-4 py-3 text-sm space-y-0.5">
                <p className="font-semibold text-gray-900">{selected.name}</p>
                {selected.city && <p className="text-gray-500">{selected.city}</p>}
                {selected.address && <p className="text-gray-400 text-xs">{selected.address}</p>}
                {selected.google_rating && (
                  <p className="text-xs text-gray-500 mt-1">
                    Google rating: <span className="font-medium">{selected.google_rating} / 5</span>
                  </p>
                )}
              </div>
            )}

            {error && <p className="text-sm text-red-600">{error}</p>}

            <div className="flex gap-2 pt-1">
              <button
                type="submit"
                disabled={!selected || saving}
                className="flex-1 bg-orange-500 hover:bg-orange-600 disabled:opacity-40 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
              >
                {saving ? 'Adding…' : 'Add place'}
              </button>
              <button
                type="button"
                onClick={onClose}
                className="text-sm text-gray-500 hover:text-gray-900 px-4 py-2 transition-colors"
              >
                Cancel
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  )
}

// ─── Main page ─────────────────────────────────────────────────────────────

export default function Dashboard() {
  const { user, logout } = useAuth()

  const [places, setPlaces] = useState([])
  const [loading, setLoading] = useState(true)
  const [city, setCity] = useState('')
  const [activeId, setActiveId] = useState(null)

  const [ratingForms, setRatingForms] = useState({})
  const [saving, setSaving] = useState(null)

  const [showAddPlace, setShowAddPlace] = useState(false)

  const fetchPlaces = (filterCity) => {
    setLoading(true)
    getPlaces(filterCity)
      .then((res) => setPlaces(res.data.data))
      .finally(() => setLoading(false))
  }

  useEffect(() => { fetchPlaces(city) }, [city])

  const handleLogout = async () => {
    await apiLogout().catch(() => {})
    logout()
  }

  const cities = [...new Set(places.map((p) => p.city).filter(Boolean))].sort()

  const scatterData = places
    .filter((p) => p.rating_count > 0)
    .map((p) => ({
      x: parseFloat(p.avg_price) || 0,
      y: parseFloat(p.avg_rating) || 0,
      name: p.name,
      city: p.city,
      id: p.google_place_id,
      count: p.rating_count,
      myRating: p.my_rating,
      googleRating: p.google_rating,
      hypeIndex: p.hype_index,
    }))

  const openRatingForm = (place) => {
    const existing = place.my_rating
    setRatingForms((prev) => ({
      ...prev,
      [place.google_place_id]: {
        price: existing ? parseFloat(existing.price) : '',
        rating: existing ? parseFloat(existing.rating) : '',
        note: existing?.note ?? '',
      },
    }))
  }

  const closeRatingForm = (id) => {
    setRatingForms((prev) => {
      const next = { ...prev }
      delete next[id]
      return next
    })
  }

  const updateRatingField = (id, field, value) => {
    setRatingForms((prev) => ({
      ...prev,
      [id]: { ...prev[id], [field]: value },
    }))
  }

  const handleRatingSubmit = async (e, googlePlaceId) => {
    e.preventDefault()
    setSaving(googlePlaceId)
    try {
      await upsertRating(googlePlaceId, ratingForms[googlePlaceId])
      closeRatingForm(googlePlaceId)
      fetchPlaces(city)
    } finally {
      setSaving(null)
    }
  }

  const handleDeleteRating = async (googlePlaceId) => {
    if (!confirm('Remove your rating?')) return
    await deleteRating(googlePlaceId)
    fetchPlaces(city)
  }

  const sorted = [...places].sort(
    (a, b) => (parseFloat(b.avg_rating) || 0) - (parseFloat(a.avg_rating) || 0)
  )

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
        <span className="font-bold text-lg tracking-tight text-gray-900">The Slice Report</span>
        <div className="flex items-center gap-4">
          {user ? (
            <>
              <span className="text-sm text-gray-500">{user.name}</span>
              <button
                onClick={handleLogout}
                className="text-sm text-gray-500 hover:text-gray-900 transition-colors"
              >
                Sign out
              </button>
            </>
          ) : (
            <>
              <Link to="/login" className="text-sm text-gray-500 hover:text-gray-900 transition-colors">
                Sign in
              </Link>
              <Link
                to="/register"
                className="text-sm bg-orange-500 hover:bg-orange-600 text-white font-medium px-3 py-1.5 rounded-lg transition-colors"
              >
                Register
              </Link>
            </>
          )}
        </div>
      </header>

      <main className="max-w-4xl mx-auto px-6 py-8 space-y-6">
        {/* Filter + actions bar */}
        <div className="flex items-center gap-3">
          <select
            value={city}
            onChange={(e) => setCity(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-orange-500"
          >
            <option value="">All cities</option>
            {cities.map((c) => (
              <option key={c} value={c}>{c}</option>
            ))}
          </select>
          {city && (
            <button
              onClick={() => setCity('')}
              className="text-sm text-gray-400 hover:text-gray-700 transition-colors"
            >
              Clear
            </button>
          )}
          <div className="flex-1" />
          {user && (
            <button
              onClick={() => setShowAddPlace(true)}
              className="text-sm bg-orange-500 hover:bg-orange-600 text-white font-medium px-4 py-2 rounded-lg transition-colors"
            >
              + Add place
            </button>
          )}
        </div>

        {/* Scatter plot */}
        {scatterData.length > 0 && (
          <div className="bg-white border border-gray-200 rounded-xl p-5">
            <p className="text-xs font-medium text-gray-400 uppercase tracking-widest mb-4">
              Price vs. Rating{city ? ` — ${city}` : ''}
            </p>
            <ResponsiveContainer width="100%" height={280}>
              <ScatterChart margin={{ top: 10, right: 20, bottom: 24, left: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
                <XAxis
                  type="number"
                  dataKey="x"
                  name="Price"
                  tickFormatter={(v) => `$${v}`}
                  tick={{ fontSize: 11, fill: '#94a3b8' }}
                  label={{ value: 'avg price', position: 'insideBottom', offset: -12, fontSize: 11, fill: '#94a3b8' }}
                />
                <YAxis
                  type="number"
                  dataKey="y"
                  name="Rating"
                  domain={[0, 5]}
                  ticks={[0, 1, 2, 3, 4, 5]}
                  tick={{ fontSize: 11, fill: '#94a3b8' }}
                  label={{ value: 'avg rating', angle: -90, position: 'insideLeft', fontSize: 11, fill: '#94a3b8' }}
                />
                <ZAxis range={[50, 50]} />
                <Tooltip content={<ChartTooltip />} cursor={false} />
                <Scatter
                  data={scatterData}
                  shape={(props) => (
                    <ScatterDot {...props} activeId={activeId} onSelect={setActiveId} />
                  )}
                />
              </ScatterChart>
            </ResponsiveContainer>
            <div className="flex items-center gap-4 mt-2 text-xs text-gray-400">
              {user && (
                <span>
                  <span className="inline-block w-2 h-2 rounded-full bg-orange-400 mr-1 align-middle" />
                  your rating
                </span>
              )}
              <span>
                <span className="inline-block w-2 h-2 rounded-full bg-red-400 mr-1 align-middle" />
                overhyped
              </span>
              <span>
                <span className="inline-block w-2 h-2 rounded-full bg-slate-400 mr-1 align-middle" />
                consensus
              </span>
              <span>
                <span className="inline-block w-2 h-2 rounded-full bg-green-500 mr-1 align-middle" />
                underrated
              </span>
            </div>
          </div>
        )}

        {/* Place cards */}
        {loading ? (
          <p className="text-sm text-gray-400">Loading…</p>
        ) : places.length === 0 ? (
          <p className="text-sm text-gray-400">No places yet.{user ? ' Add the first one!' : ''}</p>
        ) : (
          <div className="space-y-3">
            {sorted.map((place) => {
              const isActive = place.google_place_id === activeId
              const formOpen = place.google_place_id in ratingForms
              const form = ratingForms[place.google_place_id]
              const label = hypeLabel(place.hype_index)

              return (
                <div
                  key={place.google_place_id}
                  onClick={() => setActiveId(place.google_place_id)}
                  className={`bg-white border rounded-xl px-5 py-4 cursor-pointer transition-all ${
                    isActive
                      ? 'border-orange-400 ring-1 ring-orange-200'
                      : 'border-gray-200 hover:border-gray-300'
                  }`}
                >
                  <div className="flex items-start gap-4">
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 flex-wrap">
                        <p className="font-semibold text-gray-900 leading-snug">{place.name}</p>
                        {label && (
                          <span className={`text-xs rounded-full px-2 py-0.5 font-medium ${
                            label === 'Overhyped'
                              ? 'bg-red-50 text-red-600 border border-red-200'
                              : label === 'Underrated'
                              ? 'bg-green-50 text-green-600 border border-green-200'
                              : 'bg-gray-50 text-gray-500 border border-gray-200'
                          }`}>
                            {label}
                          </span>
                        )}
                      </div>
                      {place.city && <p className="text-xs text-gray-400 mt-0.5">{place.city}</p>}
                      {place.address && <p className="text-xs text-gray-400 truncate">{place.address}</p>}
                      {place.google_rating && (
                        <p className="text-xs text-gray-400 mt-0.5">
                          Google: {parseFloat(place.google_rating).toFixed(1)} / 5
                          {place.hype_index !== null && (
                            <span className={`ml-2 font-medium ${
                              place.hype_index > 0.5 ? 'text-red-500' : place.hype_index < -0.5 ? 'text-green-500' : 'text-gray-400'
                            }`}>
                              ({place.hype_index > 0 ? '+' : ''}{place.hype_index})
                            </span>
                          )}
                        </p>
                      )}
                    </div>
                    <div className="flex items-center gap-5 shrink-0 text-right">
                      {place.avg_rating != null ? (
                        <div>
                          <p className="text-2xl font-bold text-orange-500 leading-none">
                            {parseFloat(place.avg_rating).toFixed(1)}
                          </p>
                          <p className="text-xs text-gray-400 mt-0.5">
                            / 5 · {place.rating_count} {place.rating_count === 1 ? 'rating' : 'ratings'}
                          </p>
                        </div>
                      ) : (
                        <p className="text-xs text-gray-300">no ratings</p>
                      )}
                      {place.avg_price != null && (
                        <div>
                          <p className="text-lg font-semibold text-gray-700">
                            ${parseFloat(place.avg_price).toFixed(2)}
                          </p>
                          <p className="text-xs text-gray-400">avg price</p>
                        </div>
                      )}
                    </div>
                  </div>

                  {place.my_rating && !formOpen && (
                    <div className="mt-3 flex items-center gap-3 flex-wrap">
                      <span className="text-xs bg-orange-50 text-orange-700 border border-orange-200 rounded-full px-2.5 py-0.5">
                        You: {parseFloat(place.my_rating.rating).toFixed(1)} / 5 · ${parseFloat(place.my_rating.price).toFixed(2)}
                        {place.my_rating.note && ` · "${place.my_rating.note}"`}
                      </span>
                      <button
                        onClick={(e) => { e.stopPropagation(); openRatingForm(place) }}
                        className="text-xs text-gray-400 hover:text-orange-500 transition-colors"
                      >
                        Edit
                      </button>
                      <button
                        onClick={(e) => { e.stopPropagation(); handleDeleteRating(place.google_place_id) }}
                        className="text-xs text-gray-400 hover:text-red-500 transition-colors"
                      >
                        Remove
                      </button>
                    </div>
                  )}

                  {user && !place.my_rating && !formOpen && (
                    <div className="mt-3">
                      <button
                        onClick={(e) => { e.stopPropagation(); openRatingForm(place) }}
                        className="text-xs font-medium text-orange-500 hover:text-orange-600 transition-colors"
                      >
                        + Rate this place
                      </button>
                    </div>
                  )}

                  {formOpen && (
                    <form
                      onSubmit={(e) => handleRatingSubmit(e, place.google_place_id)}
                      onClick={(e) => e.stopPropagation()}
                      className="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-3 items-end"
                    >
                      <div>
                        <label className="block text-xs text-gray-500 mb-1">Rating (0–5)</label>
                        <input
                          type="number"
                          step="0.1"
                          min="0"
                          max="5"
                          required
                          value={form.rating}
                          onChange={(e) => updateRatingField(place.google_place_id, 'rating', e.target.value)}
                          className="w-24 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                        />
                      </div>
                      <div>
                        <label className="block text-xs text-gray-500 mb-1">Price ($)</label>
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          required
                          value={form.price}
                          onChange={(e) => updateRatingField(place.google_place_id, 'price', e.target.value)}
                          className="w-24 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                        />
                      </div>
                      <div className="flex-1 min-w-40">
                        <label className="block text-xs text-gray-500 mb-1">Note (optional)</label>
                        <input
                          type="text"
                          value={form.note}
                          onChange={(e) => updateRatingField(place.google_place_id, 'note', e.target.value)}
                          placeholder="e.g. Crispy crust, perfect char"
                          className="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                        />
                      </div>
                      <div className="flex gap-2">
                        <button
                          type="submit"
                          disabled={saving === place.google_place_id}
                          className="bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors"
                        >
                          {saving === place.google_place_id ? 'Saving…' : 'Save'}
                        </button>
                        <button
                          type="button"
                          onClick={() => closeRatingForm(place.google_place_id)}
                          className="text-sm text-gray-400 hover:text-gray-700 px-2 transition-colors"
                        >
                          Cancel
                        </button>
                      </div>
                    </form>
                  )}
                </div>
              )
            })}
          </div>
        )}
      </main>

      {showAddPlace && (
        <AddPlaceModal
          onClose={() => setShowAddPlace(false)}
          onAdded={() => fetchPlaces(city)}
        />
      )}
    </div>
  )
}
