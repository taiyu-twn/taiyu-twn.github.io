const fs = require('fs')

const fetch = require('node-fetch')

const routingFolder = './src/assets/routing/'
const cacheFile = './scripts/routingCache.json'

let routingFiles = []

// Synchronous: stop any further execution of your code until the read process ends.
fs.readdirSync(routingFolder).forEach(file => {
  console.log(file)
  routingFiles.push(file)
})

let cache = JSON.parse(fs.readFileSync(cacheFile, 'utf8') || '{}')

routingFiles.map(file => {
  if (file == 'index.js') return

  const filePath = `./src/assets/routing/${file}`
  let data = JSON.parse(fs.readFileSync(filePath, 'utf8'))

  let callCount = {
    cache: 0,
    api: 0,
  }

  Promise.all(
    Object.entries(data).map(([key, val]) => {
      console.log('readData: ', key)
      const path = {
        dep: {
          lat: val.dep_lat,
          lng: val.dep_lng,
        },
        arr: {
          lat: val.arr_lat,
          lng: val.arr_lng,
        },
      }

      return routing(path)
        .then(({ res, status }) => {
          data[key].routing = res
          callCount[status] += 1
          if ((callCount.cache + callCount.api) % 1500 == 1) {
            fs.writeFileSync(cacheFile, JSON.stringify(cache))
            console.log(file, callCount)
          }
        })
        .catch(e => console.warn(e))
    })
  ).then(() => {
    fs.writeFileSync(filePath, JSON.stringify(data, null, '\t'))
    fs.writeFileSync(cacheFile, JSON.stringify(cache))
    console.log(callCount)
  })
})

function routing({ dep, arr }) {
  const service = 'route' // One of the following values:  route ,  nearest ,  table ,  match ,  trip ,  tile
  const version = 'v1' // Version of the protocol implemented by the service.  v1 for all OSRM 5.x installations
  const profile = 'foot' // Mode of transportation, is determined statically by the Lua profile that is used to prepare the data using  osrm-extract . Typically  car ,  bike or  foot if using one of the supplied profiles.
  const coordinates = `${dep.lng},${dep.lat};${arr.lng},${arr.lat}`
  // const format = 'json' // Only `json` is supported at the moment. This parameter is optional and defaults to `json`.

  const options = {
    // Doc: http://project-osrm.org/docs/v5.15.2/api/?language=JavaScript#general-options
    geometries: 'geojson', // polyline (default),  polyline6 , geojson	Returned route geometry format (influences overview and per step)
    overview: 'full', // simplified (default),  full , false	Add overview geometry either full, simplified according to highest zoom level it could be display on, or not at all.
  }

  const optionsString = Object.entries(options)
    .map(([key, value]) => `${key}=${value}`)
    .join('&')

  const fetchUrl = `http://router.project-osrm.org/${service}/${version}/${profile}/${coordinates}?${optionsString}`
  // console.log(fetchUrl)

  if (cache[profile][coordinates]) {
    return Promise.resolve({
      res: cache[profile][coordinates],
      status: 'cache',
    })
  } else {
    return fetchJSON(fetchUrl).then(res => {
      cache[profile][coordinates] = res
      return {
        res,
        status: 'api',
      }
    })
  }
}

function fetchJSON(url) {
  url = url.replace(/\s/g, '')
  const checkStatus = res => {
    if (res.status >= 200 && res.status < 300) {
      return Promise.resolve(res)
    } else {
      return Promise.reject(new Error(res.statusText))
    }
  }

  return fetch(url)
    .then(checkStatus)
    .then(res => res.json())
    .then(res => res.routes[0].geometry)
}
