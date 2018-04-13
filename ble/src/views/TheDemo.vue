<template>
  <div id="theDemo" style="display: unset;">
    <h1>Sense Bike</h1>
    <img src="@/assets/photo/src/Heart_2D_00.jpg" alt="">
    <div id="connection">
      <label for="connectBLE"><i class="fa fa-bluetooth"/> BLE</label>
      <el-switch
        id="connectBLE"
        v-model="connecting"
        active-color="#13ce66"
        inactive-color="#ff4949"
        style="margin-top: -4px;"
        @change="connect"
      />
      <span v-if="connected && bat_level" id="bat_status">
        <i :class="bat_icon"/>
        <i>{{ bat_level }} %</i>
      </span>
      
      <p v-if="connecting && !connected"><i class="fa fa-spinner fa-pulse"/> CONNECTING</p>
    </div>
    <br>
    <div v-show="connected" id="console">
      <div id="table">
        <el-table
          v-loading="!sensorsValueArray.length"
          :data="sensorsValueArray"
          :header-cell-style="{textAlign: 'center'}"
          size="small"
          height="200"
        >
          <el-table-column
            prop="name"
            label="Sensor"
            width="100"
            sortable
          />
          <el-table-column
            prop="value"
            label="Value"
          />
        </el-table>
      </div>
      <div v-show="false" id="markers" >
        <span ref="marker">
          <i :style="{color: '#208CF1', opacity: 0.4}" style="font-size:28px;" class="fa fa-circle"/>
        </span>
      </div>
    </div>
  </div>
</template>

<script>
import _ from 'lodash'

const consoleError = error => console.error(error)

export default {
  name: 'TheDemo',

  data() {
    const tagSensorIds = {
      // IRT: 'a0',
      // OPT: 'a7',
      // BAR: 'a4',
    }

    const computedTagUUID = id => ({
      serv: `f000a${id}0-0451-4000-b000-000000000000`,
      char: `f000a${id}1-0451-4000-b000-000000000000`,
      conf: `f000a${id}2-0451-4000-b000-000000000000`,
    })

    const uuids = Object.entries(tagSensorIds).map(([sensor, id]) => ({
      name: sensor,
      ...computedTagUUID(id),
    }))

    return {
      map: {},
      ready: false,
      loaded: false,
      marker: null,
      connected: false,
      connecting: false,
      services: [],
      enabled: false,
      uuids: uuids,
      sensorsValue: {},
      interval: null,
      intervalTime: 1500,
      reading: false,
      watchGPS: null,
      bat_level: 0,
      bat_uuid: {
        name: 'BAT',
        serv: 'battery_service',
        char: 'battery_level',
      },
      sensorChars: {},
    }
  },
  computed: {
    bat_icon() {
      let bat_status = ''

      if (this.bat_level >= 75) bat_status = 'full'
      else if (this.bat_level >= 50) bat_status = 'three-quarters'
      else if (this.bat_level >= 25) bat_status = 'quarter'
      else bat_status = 'empty'

      return `fa fa-battery-${bat_status} fa-rotate-270`
    },
    sensorsValueArray() {
      return _.toArray(this.sensorsValue)
    },
  },
  watch: {
    connecting(val) {
      if (!val) {
        // reset data value
        this.marker = null
        this.connected = false
        this.services = []
        this.enabled = false
        this.sensorsValue = []
        this.reading = false
        this.bat_level = 0
      }
    },
  },
  created() {
    this.setInterval(false)
    this.watchGPS = navigator.geolocation.watchPosition(this.GPSUpdated)
    // this.watchGPS = navigator.geolocation.getCurrentPosition(this.GPSUpdated) // may unnecessary
  },
  beforeDestroy() {
    this.interval = clearInterval(this.interval)
    navigator.geolocation.clearWatch(this.watchGPS)
  },
  methods: {
    connect(connect) {
      if (connect) {
        this.requestDevice()
      } else {
        this.service = null
        this.connected = false
      }
    },
    setInterval(set) {
      // BOOLEAN
      if (set && !this.interval) {
        this.interval = setInterval(() => {
          this.getSensorUpdate()
        }, this.intervalTime)
      } else {
        this.interval = clearInterval(this.interval)
      }
    },
    getSensorUpdate() {
      if (this.connected && !this.reading) {
        this.reading = true
        this.getSensorsValue().then(() => (this.reading = false))
      }
    },

    GPSUpdated(position) {
      console.log('GPS updated', { position })
      this.position = {
        lat: position.coords.latitude,
        lng: position.coords.longitude,
      }
      this.setPositionMarker()
    },
    // setPositionMarker() {
    //   if (!this.position) return

    //   const { lng, lat } = this.position
    //   const marker = this.$refs.marker

    //   if (this.marker) this.marker.remove()
    //   this.marker = new mapboxgl.Marker(marker)
    //     .setLngLat([lng, lat])
    //     .addTo(this.map)

    //   this.map.easeTo({ center: [lng, lat], zoom: 15 })
    // },
    requestDevice() {
      console.log('requestDevice()', this.uuids)
      navigator.bluetooth
        .requestDevice({
          acceptAllDevices: true,
          optionalServices: this.uuids
            .map(uuid => uuid.serv)
            .concat(this.bat_uuid.serv),
        })
        .then(device => {
          console.log(device)
          return device.gatt.connect()
        })
        .then(server => {
          // Getting Services...
          this.server = server
          return Promise.all(
            this.uuids.map(uuid => server.getPrimaryService(uuid.serv))
          )
        })
        .then(services => {
          console.log({ services })
          this.services = services
          this.connected = true
          return this.getTagBettery()
        })
        .then(this.enableServices)
        .then(this.getSensorsValue)
        .then(this.setSensorNotifications)
        .then(() => (this.connected = true))
        .catch(error => {
          this.connecting = false
          console.error(error)
        })
    },
    setCharNotifications({ char, set, callback }) {
      if (set) {
        return char.startNotifications().then(() => {
          console.log('> Notifications started', char.uuid)
          char.addEventListener('characteristicvaluechanged', callback)
        })
      } else {
        return char.stopNotifications().then(() => {
          console.log('> Notifications stoped', char.uuid)
          char.removeEventListener('characteristicvaluechanged', callback)
        })
      }
    },
    getTagBettery() {
      console.log('getTagBettery()')
      return this.server
        .getPrimaryService(this.bat_uuid.serv)
        .then(service => service.getCharacteristic(this.bat_uuid.char))
        .then(char => {
          return this.readBatValue(char).then(() =>
            this.setCharNotifications({
              char,
              set: true,
              callback: event => {
                const value = event.target.value
                this.bat_level = value.getUint8(0)
              },
            })
          )
        })
    },
    readBatValue(char) {
      console.log('readBatValue', { char })
      return char
        .readValue()
        .then(value => value.getUint8(0))
        .then(value => (this.bat_level = value))
    },
    getServChar(servId, charId) {
      const service = this.services.find(f => f.uuid == servId)
      // console.log('readCharValue()', { service })

      return service.getCharacteristic(charId)
    },
    getSensorsValue() {
      console.log('getSensorsValue()')
      return Promise.resolve(this.uuids)
        .mapSeries(uuid =>
          this.getServChar(uuid.serv, uuid.char)
            .then(char => {
              // save this.sensorChars
              char.name = uuid.name
              this.sensorChars[uuid.char] = char

              // Reading Characteristic...
              // console.log({ char })
              return char.readValue()
            })
            .then(value => value.getUint8(0))
            .then(value => ({
              charId: uuid.char,
              name: uuid.name,
              value: value,
            }))
        )
        .then(sensorsValue => {
          // console.log('getSensorsValue() done', sensorsValue)
          this.sensorsValue = _.keyBy(sensorsValue, 'charId')
        })
        .catch(consoleError)
    },
    enableServices() {
      return Promise.resolve(this.uuids)
        .mapSeries(uuid =>
          this.getServChar(uuid.serv, uuid.conf)
            .then(char => {
              // Reading Characteristic...
              // console.log({ char })
              const bufValue = Uint8Array.of(0x01)
              // console.log({ bufValue })

              return char.writeValue(bufValue, {
                type: 'without-response',
              })
            })
            .catch(consoleError)
        )
        .then(result => console.log('enableServices() done', { result }))
    },
    setSensorNotifications() {
      return Promise.resolve(_.toArray(this.sensorChars)).mapSeries(char =>
        this.setCharNotifications({
          char,
          set: true,
          callback: this.handleSensorValueChanged(char),
        })
      )
    },
    handleSensorValueChanged(char) {
      console.log('handleSensorValueChanged', char)
      return event => {
        console.log(`${char.name} updated`)

        const value = event.target.value
        this.sensorsValue[char.uuid] = {
          charId: char.uuid,
          name: char.name,
          value: value.getUint8(0),
        }
      }
    },
  },
}
</script>

<style lang="scss" scoped>
#theDemo {
  width: 100%;
  text-align: center;
}

#bat_status {
  position: absolute;
  margin-left: 6px;
}
</style>
