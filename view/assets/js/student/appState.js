export default class AppState {
  constructor() {
    this.solicitudesData = [];
    this.filteredData   = [];
    this.isLoading      = false;
  }

  setSolicitudes(data = []) {
    this.solicitudesData = Array.isArray(data) ? data : [];
    this.filteredData    = [...this.solicitudesData];
  }

  getSolicitudes()   { return this.solicitudesData; }
  getFilteredData()  { return this.filteredData;    }
  setFilteredData(d) { this.filteredData = d;       }
}
