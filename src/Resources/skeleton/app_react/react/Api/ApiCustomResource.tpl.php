import axios from "axios";

const BASE_API_URL = 'http://127.0.0.1:8000/api';

const findAll = () => {
    return axios.get(`${BASE_API_URL}/<?= $api_resource_name ?>s`)
        .then(response => response.data);
}

const create = (<?= $api_resource_name ?>) => {
    axios.post(`${BASE_API_URL}/<?= $api_resource_name ?>s`, <?= $api_resource_name ?>);
}

const find = (id) => {
    return axios.get(`${BASE_API_URL}/<?= $api_resource_name ?>s/${id}`)
        .then(response => response.data);
}

const update = (id, <?= $api_resource_name ?>) => {
    axios.put(`${ BASE_API_URL}/<?= $api_resource_name ?>s/${id}`, <?= $api_resource_name ?>);
}

const remove = (id) => {
    axios.delete(`${BASE_API_URL}/<?= $api_resource_name ?>s/${id}`);
}

export default { findAll, find, create, update, remove };